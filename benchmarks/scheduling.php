<?php

/**
 * Scheduling benchmark: bitmask vs. interval-overlap availability checks.
 *
 * Generates a deterministic set of doctor schedules and bookings, verifies that
 * both approaches produce identical answers, then times them.
 *
 * Usage:
 *   php benchmarks/scheduling.php [--doctors=50] [--rounds=9] [--seed=42] [--markdown]
 */

declare(strict_types=1);

const DAY_START = 8 * 60;   // 08:00, in minutes
const DAY_END   = 17 * 60;  // 17:00
const SLOT      = 15;       // minutes represented by one bit

$opts     = getopt('', ['doctors::', 'rounds::', 'seed::', 'markdown']);
$doctors  = (int) ($opts['doctors'] ?? 50);
$rounds   = (int) ($opts['rounds'] ?? 9);
$seed     = (int) ($opts['seed'] ?? 42);
$markdown = isset($opts['markdown']);

// ============================================================
//  The two approaches
// ============================================================

// Same encoding as calculateSlotMask() in the controllers: bit 0 = 08:00, bit 1 = 08:15, ...
function rangeToMask(int $start, int $end): int
{
    $startBit = intdiv($start - DAY_START, SLOT);
    $bits     = intdiv($end - $start, SLOT);
    return ((1 << $bits) - 1) << $startBit;
}

// Interval approach: availability and bookings stored as [start, end] rows.
function intervalIsFree(array $avail, array $bookings, int $start, int $end): bool
{
    $inside = false;
    foreach ($avail as [$as, $ae]) {
        if ($start >= $as && $end <= $ae) {
            $inside = true;
            break;
        }
    }
    if (!$inside) {
        return false;
    }
    foreach ($bookings as [$bs, $be]) {
        if ($start < $be && $end > $bs) {
            return false;
        }
    }
    return true;
}

function intervalFreeSlots(array $avail, array $bookings, array $candidates, int $duration): array
{
    $free = [];
    foreach ($candidates as $start) {
        if (intervalIsFree($avail, $bookings, $start, $start + $duration)) {
            $free[] = $start;
        }
    }
    return $free;
}

// Bitmask approach: one schedule integer per weekday, one integer per booking.
function bitmaskIsFree(int $scheduleMask, array $bookingMasks, int $requestedMask): bool
{
    $booked = 0;
    foreach ($bookingMasks as $mask) {
        $booked |= $mask;
    }
    return (($scheduleMask & ~$booked) & $requestedMask) === $requestedMask;
}

function bitmaskFreeSlots(int $scheduleMask, array $bookingMasks, array $candidateMasks): array
{
    $booked = 0;
    foreach ($bookingMasks as $mask) {
        $booked |= $mask;
    }
    $available = $scheduleMask & ~$booked;

    $free = [];
    foreach ($candidateMasks as $start => $mask) {
        if (($available & $mask) === $mask) {
            $free[] = $start;
        }
    }
    return $free;
}

// ============================================================
//  Data generation
// ============================================================

// Candidate start times on the duration grid, like decodeMaskToSlots() in the patient controller.
function candidates(int $duration): array
{
    $starts = [];
    for ($t = DAY_START; $t + $duration <= DAY_END; $t += $duration) {
        $starts[] = $t;
    }
    return $starts;
}

/**
 * One doctor-day: availability ranges, bookings, and both encodings of them.
 * Ranges never touch each other so both approaches agree on containment.
 */
function makeDay(int $duration, float $occupancy): array
{
    $shapes = [
        [[8 * 60, 17 * 60]],
        [[8 * 60, 12 * 60], [13 * 60, 17 * 60]],
        [[8 * 60, 12 * 60]],
        [[13 * 60, 17 * 60]],
    ];
    $avail = mt_rand(1, 7) <= 2 ? [] : $shapes[mt_rand(0, count($shapes) - 1)];

    $open = [];
    foreach (candidates($duration) as $start) {
        foreach ($avail as [$as, $ae]) {
            if ($start >= $as && $start + $duration <= $ae) {
                $open[] = $start;
                break;
            }
        }
    }
    shuffle($open);
    $taken = array_slice($open, 0, (int) round(count($open) * $occupancy));

    $bookings = array_map(fn ($s) => [$s, $s + $duration], $taken);
    $scheduleMask = 0;
    foreach ($avail as [$as, $ae]) {
        $scheduleMask |= rangeToMask($as, $ae);
    }

    $all = candidates($duration);
    $requestStart = $all[mt_rand(0, count($all) - 1)];

    return [
        'duration'      => $duration,
        'avail'         => $avail,
        'bookings'      => $bookings,
        'scheduleMask'  => $scheduleMask,
        'bookingMasks'  => array_map(fn ($b) => rangeToMask($b[0], $b[1]), $bookings),
        'request'       => [$requestStart, $requestStart + $duration],
        'requestMask'   => rangeToMask($requestStart, $requestStart + $duration),
    ];
}

// A full "weekly availability page": every doctor x 7 days, like getPatientAppointments().
function makeWeek(int $doctors, array $durations, float $occupancy): array
{
    $days = [];
    for ($d = 0; $d < $doctors; $d++) {
        $duration = $durations[$d % count($durations)];
        for ($w = 0; $w < 7; $w++) {
            $days[] = makeDay($duration, $occupancy);
        }
    }
    return $days;
}

// ============================================================
//  Harness
// ============================================================

function verify(array $days, array $candidates, array $candidateMasks): void
{
    foreach ($days as $i => $day) {
        $dur = $day['duration'];
        $a = intervalFreeSlots($day['avail'], $day['bookings'], $candidates[$dur], $dur);
        $b = bitmaskFreeSlots($day['scheduleMask'], $day['bookingMasks'], $candidateMasks[$dur]);
        if ($a !== $b) {
            fwrite(STDERR, "Mismatch in free slots for doctor-day #$i\n");
            exit(1);
        }
        [$s, $e] = $day['request'];
        if (intervalIsFree($day['avail'], $day['bookings'], $s, $e)
            !== bitmaskIsFree($day['scheduleMask'], $day['bookingMasks'], $day['requestMask'])) {
            fwrite(STDERR, "Mismatch in conflict check for doctor-day #$i\n");
            exit(1);
        }
    }
}

// Median seconds per call of $fn, auto-repeating until each round takes at least ~50ms.
function measure(callable $fn, int $rounds): float
{
    $start = hrtime(true);
    $fn();
    $once = max(1, hrtime(true) - $start);
    $reps = max(1, (int) ceil(50_000_000 / $once));

    $samples = [];
    for ($r = 0; $r < $rounds; $r++) {
        $start = hrtime(true);
        for ($i = 0; $i < $reps; $i++) {
            $fn();
        }
        $samples[] = (hrtime(true) - $start) / $reps / 1e9;
    }
    sort($samples);
    return $samples[intdiv(count($samples), 2)];
}

function fmtTime(float $seconds): string
{
    return match (true) {
        $seconds >= 1e-3 => sprintf('%.2f ms', $seconds * 1e3),
        $seconds >= 1e-6 => sprintf('%.2f µs', $seconds * 1e6),
        default          => sprintf('%.0f ns', $seconds * 1e9),
    };
}

function printTable(array $headers, array $rows, bool $markdown): void
{
    if ($markdown) {
        echo '| ' . implode(' | ', $headers) . " |\n";
        echo '|' . implode('|', array_map(fn ($h) => ' ' . str_repeat('-', max(3, mb_strlen($h))) . ' ', $headers)) . "|\n";
        foreach ($rows as $row) {
            echo '| ' . implode(' | ', $row) . " |\n";
        }
        echo "\n";
        return;
    }
    $widths = array_map('mb_strlen', $headers);
    foreach ($rows as $row) {
        foreach ($row as $i => $cell) {
            $widths[$i] = max($widths[$i], mb_strlen($cell));
        }
    }
    $line = fn ($cells) => '  ' . implode('   ', array_map(
        fn ($c, $i) => $i === 0 ? $c . str_repeat(' ', $widths[$i] - mb_strlen($c)) : str_repeat(' ', $widths[$i] - mb_strlen($c)) . $c,
        $cells,
        array_keys($cells)
    )) . "\n";
    echo $line($headers);
    echo '  ' . implode('   ', array_map(fn ($w) => str_repeat('─', $w), $widths)) . "\n";
    foreach ($rows as $row) {
        echo $line($row);
    }
    echo "\n";
}

// ============================================================
//  Run
// ============================================================

mt_srand($seed);

$durations = [15, 30, 45, 60];
$candidates = [];
$candidateMasks = [];
foreach ($durations as $dur) {
    $candidates[$dur] = candidates($dur);
    foreach ($candidates[$dur] as $start) {
        $candidateMasks[$dur][$start] = rangeToMask($start, $start + $dur);
    }
}

$heading = fn ($text) => print($markdown ? "### $text\n\n" : "\n$text\n");

printf(
    "%sScheduling benchmark: bitmask vs. interval overlap (PHP %s, seed %d, median of %d rounds)\n%s",
    $markdown ? '' : "\n",
    PHP_VERSION,
    $seed,
    $rounds,
    $markdown ? "\n" : ''
);

// 1. Weekly availability page at different occupancy levels.
$heading("1. Weekly availability page ($doctors doctors × 7 days, mixed 15/30/45/60-min slots)");
$rows = [];
foreach ([0.25, 0.50, 0.90] as $occupancy) {
    $week = makeWeek($doctors, $durations, $occupancy);
    verify($week, $candidates, $candidateMasks);

    $interval = measure(function () use ($week, $candidates) {
        foreach ($week as $day) {
            intervalFreeSlots($day['avail'], $day['bookings'], $candidates[$day['duration']], $day['duration']);
        }
    }, $rounds);
    $bitmask = measure(function () use ($week, $candidateMasks) {
        foreach ($week as $day) {
            bitmaskFreeSlots($day['scheduleMask'], $day['bookingMasks'], $candidateMasks[$day['duration']]);
        }
    }, $rounds);

    $rows[] = [sprintf('%d%% booked', $occupancy * 100), fmtTime($interval), fmtTime($bitmask), sprintf('%.1f×', $interval / $bitmask)];
}
printTable(['Occupancy', 'Interval', 'Bitmask', 'Speedup'], $rows, $markdown);

// 2. Scaling with bookings per day: the interval approach is O(slots × bookings), bitmask is O(slots + bookings).
$heading('2. Listing free slots for one doctor-day as bookings grow (15-min slots, full-day schedule)');
$rows = [];
$fullDay = rangeToMask(DAY_START, DAY_END);
foreach ([0, 9, 18, 27, 36] as $count) {
    $starts = $candidates[15];
    shuffle($starts);
    $taken = array_slice($starts, 0, $count);
    $bookings = array_map(fn ($s) => [$s, $s + 15], $taken);
    $masks = array_map(fn ($b) => rangeToMask($b[0], $b[1]), $bookings);
    $avail = [[DAY_START, DAY_END]];

    verify([[
        'duration' => 15, 'avail' => $avail, 'bookings' => $bookings, 'scheduleMask' => $fullDay,
        'bookingMasks' => $masks, 'request' => [DAY_START, DAY_START + 15], 'requestMask' => 1,
    ]], $candidates, $candidateMasks);

    $interval = measure(fn () => intervalFreeSlots($avail, $bookings, $candidates[15], 15), $rounds);
    $bitmask  = measure(fn () => bitmaskFreeSlots($fullDay, $masks, $candidateMasks[15]), $rounds);

    $rows[] = [(string) $count, fmtTime($interval), fmtTime($bitmask), sprintf('%.1f×', $interval / $bitmask)];
}
printTable(['Bookings', 'Interval', 'Bitmask', 'Speedup'], $rows, $markdown);

// 3. Single conflict check, i.e. validating one booking request.
$heading("3. Validating one booking request (averaged over $doctors doctors × 7 days, 50% booked)");
$week = makeWeek($doctors, $durations, 0.50);
verify($week, $candidates, $candidateMasks);
$n = count($week);
$interval = measure(function () use ($week) {
    foreach ($week as $day) {
        intervalIsFree($day['avail'], $day['bookings'], $day['request'][0], $day['request'][1]);
    }
}, $rounds) / $n;
$bitmask = measure(function () use ($week) {
    foreach ($week as $day) {
        bitmaskIsFree($day['scheduleMask'], $day['bookingMasks'], $day['requestMask']);
    }
}, $rounds) / $n;
printTable(['Check', 'Interval', 'Bitmask', 'Speedup'], [['Is slot free?', fmtTime($interval), fmtTime($bitmask), sprintf('%.1f×', $interval / $bitmask)]], $markdown);

echo $markdown ? '' : '  ', "✓ Both approaches returned identical results for every generated doctor-day.\n";
