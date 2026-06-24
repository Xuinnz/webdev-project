@extends('patient.common.patientCommon')

@section('title', 'Home')

@section('content')
    <div class="home-grid">
        <div>
            <h1 class="page-title animate-unicare-in stagger-1">Hello, {{ $userName }}!</h1>

            <section class="section animate-unicare-scale-in stagger-2">
                <h2 class="section-title">Notification</h2>
                <div class="unicare-card-dark animate-unicare-in stagger-3">
                    <p class="notification-text">{{ $notification }}</p>
                    <div class="pup-watermark"></div>
                </div>
            </section>

            <section class="section animate-unicare-in stagger-4">
                <h2 class="section-title">Appointments</h2>
                <div class="glass-panel">
                    <table class="unicare-table">
                        <thead>
                            <tr>
                                <th>Doctor</th>
                                <th>Type</th>
                                <th>Start</th>
                                <th>End</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($appointments as $index => $appointment)
                                <tr class="animate-unicare-in stagger-{{ min($index + 1, 8) }}">
                                    <td>{{ $appointment['doctor_name'] }}</td>
                                    <td>{{ $appointment['type_label'] }}</td>
                                    <td>{{ $appointment['start_time'] }}</td>
                                    <td>{{ $appointment['end_time'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="home-sidebar">
            <section class="animate-unicare-in stagger-3">
                <h2 class="section-title">{{ $calendar['label'] }}</h2>
                <div class="glass-panel">
                    <div class="calendar-header">
                        <span></span>
                        <span>Mo</span>
                        <span>Tu</span>
                        <span>We</span>
                        <span>Th</span>
                        <span>Fr</span>
                        <span>Sa</span>
                        <span>Su</span>
                    </div>

                    @foreach ($calendar['weeks'] as $weekIndex => $week)
                        <div @class([
                            'calendar-week animate-unicare-in',
                            'stagger-' . min($weekIndex + 4, 8) => true,
                        ])>
                            <div class="calendar-week-number">{{ $week['number'] }}</div>

                            @foreach ($week['days'] as $day)
                                <div @class([
                                    'calendar-day',
                                    'is-outside' => ! $day['in_month'],
                                    'is-current-month' => $day['in_month'] && ! $day['is_today'],
                                    'is-today' => $day['is_today'],
                                ])>
                                    {{ $day['date']->day }}
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="animate-unicare-in stagger-5">
                <h2 class="section-title">Messages</h2>
                <div class="unicare-card-dark unicare-card-dark--tall">
                    <div class="message-list">
                        @foreach ($messages as $index => $message)
                            <div @class(['message-pill animate-unicare-in', 'stagger-' . min($index + 1, 8) => true])>
                                {{ $message['sender_name'] }}: {{ $message['body'] }}
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
