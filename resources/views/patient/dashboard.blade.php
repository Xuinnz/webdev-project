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
                    <table class="unicare-table w-full text-left">
                        <thead>
                            <tr>
                                <th class="p-2">Doctor</th>
                                <th class="p-2">Type</th>
                                <th class="p-2">Start</th>
                                <th class="p-2">End</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($appointments as $index => $appointment)
                                <tr class="animate-unicare-in stagger-{{ min($index + 1, 8) }} border-t border-gray-700/50">
                                    <td class="p-2">{{ $appointment['doctor_name'] }}</td>
                                    <td class="p-2">
                                        <span class="badge {{ $appointment['type_label'] === 'Telemedicine' ? 'badge-blue' : 'badge-green' }}">
                                            {{ $appointment['type_label'] }}
                                        </span>
                                    </td>
                                    <td class="p-2">{{ $appointment['start_time'] }}</td>
                                    <td class="p-2">{{ $appointment['end_time'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="p-6 text-center opacity-50">
                                        You have no upcoming appointments.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="home-sidebar">
            <section class="animate-unicare-in stagger-3">
                <h2 class="section-title">{{ $calendar['label'] }}</h2>
                <div class="glass-panel p-4">
                    <div class="calendar-header grid grid-cols-8 gap-1 text-center font-bold mb-2">
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
                            'calendar-week grid grid-cols-8 gap-1 text-center animate-unicare-in mb-1',
                            'stagger-' . min($weekIndex + 4, 8) => true,
                        ])>

                            @foreach ($week['days'] as $day)
                                <div @class([
                                    'calendar-day p-1 rounded-md flex items-center justify-center',
                                    'opacity-30' => ! $day['in_month'],
                                    'hover:bg-white/10 cursor-pointer' => $day['in_month'] && ! $day['is_today'],
                                    'bg-blue-500 text-white font-bold' => $day['is_today'],
                                ])>
                                    {{ $day['date']->day }}
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </section>
<!-- 
            <section class="animate-unicare-in stagger-5 mt-6">
                <h2 class="section-title">Recent Messages</h2>
                <div class="unicare-card-dark unicare-card-dark--tall p-4 rounded-xl">
                    <div class="message-list flex flex-col gap-3">
                        @forelse ($messages as $index => $message)
                            <div @class(['message-pill p-3 bg-white/5 rounded-lg animate-unicare-in', 'stagger-' . min($index + 1, 8) => true])>
                                <div class="font-bold text-sm text-blue-300">{{ $message['sender_name'] }}</div>
                                <div class="text-sm opacity-80 mt-1">{{ $message['body'] }}</div>
                            </div>
                        @empty
                            <div class="text-center opacity-50 py-4">No recent messages.</div>
                        @endforelse
                    </div>
                </div>
            </section> -->
        </div>
    </div>
@endsection