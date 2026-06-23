@extends('doctor.common.main')

@section('title', 'Appointments')

@section('content')
    <h1 class="page-title animate-unicare-in stagger-1">Appointments</h1>

    <div class="glass-panel glass-panel--padded glass-panel--relative animate-unicare-scale-in stagger-2">
        @if ($appointments->isEmpty())
            <p class="text-sm opacity-75 py-4">No appointments found.</p>
        @else
            <table class="unicare-table">
                <thead>
                    <tr>
                        <th>Patient Name</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($appointments as $index => $appointment)
                        <tr class="animate-unicare-in stagger-{{ min($index + 1, 8) }}">
                            <td>{{ $appointment->patient_name }}</td>
                            <td>{{ $appointment->appointment_date }}</td>
                            <td>{{ $appointment->start_time }} – {{ $appointment->end_time }}</td>
                            <td>
                                <span class="inline-block rounded-full px-3 py-1 text-xs font-medium capitalize bg-white/50">
                                    {{ str_replace('_', ' ', $appointment->status) }}
                                </span>
                            </td>
                            <td>
                                @if ($appointment->patient_uuid)
                                    <a href="{{ route('doctor.patients.show', $appointment->patient_uuid) }}" class="text-sm underline">
                                        View patient
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
        <div class="pup-watermark"></div>
    </div>
@endsection
