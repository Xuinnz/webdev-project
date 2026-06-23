@extends('doctor.common.main')

@section('title', 'Dashboard')

@section('content')
    <h1 class="page-title animate-unicare-in stagger-1">Hello, {{ $doctor->name }}!</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8 animate-unicare-scale-in stagger-2">
        <div class="unicare-card-dark p-6">
            <p class="text-sm opacity-80 mb-1">Today's Appointments</p>
            <p class="text-3xl font-semibold">{{ $stats['today_appointments'] }}</p>
        </div>
        <div class="unicare-card-dark p-6">
            <p class="text-sm opacity-80 mb-1">Total Patients</p>
            <p class="text-3xl font-semibold">{{ $stats['total_patients'] }}</p>
        </div>
        <div class="unicare-card-dark p-6">
            <p class="text-sm opacity-80 mb-1">Upcoming Appointments</p>
            <p class="text-3xl font-semibold">{{ $stats['upcoming_appointments'] }}</p>
        </div>
    </div>

    <section class="section animate-unicare-in stagger-3">
        <h2 class="section-title">Today's Appointments</h2>
        <div class="glass-panel glass-panel--padded glass-panel--relative">
            @if ($todayAppointments->isEmpty())
                <p class="text-sm opacity-75 py-4">No appointments scheduled for today.</p>
            @else
                <table class="unicare-table">
                    <thead>
                        <tr>
                            <th>Patient Name</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($todayAppointments as $index => $appointment)
                            <tr class="animate-unicare-in stagger-{{ min($index + 1, 8) }}">
                                <td>{{ $appointment->patient_name }}</td>
                                <td>{{ $appointment->appointment_date }}</td>
                                <td>{{ $appointment->start_time }} – {{ $appointment->end_time }}</td>
                                <td>
                                    <span class="inline-block rounded-full px-3 py-1 text-xs font-medium capitalize bg-white/50">
                                        {{ str_replace('_', ' ', $appointment->status) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
            <div class="pup-watermark"></div>
        </div>
    </section>
@endsection
