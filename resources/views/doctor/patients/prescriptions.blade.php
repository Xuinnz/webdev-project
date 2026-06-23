@extends('doctor.common.main')

@section('title', 'Prescriptions — ' . $patient->name)

@section('content')
    <div class="mb-4 text-sm opacity-75 animate-unicare-in stagger-1">
        <a href="{{ route('doctor.patients.index') }}" class="underline">Patients</a>
        <span> / </span>
        <a href="{{ route('doctor.patients.show', $patient->uuid) }}" class="underline">{{ $patient->name }}</a>
        <span> / Prescriptions</span>
    </div>

    <h1 class="page-title animate-unicare-in stagger-1">Prescriptions</h1>

    @include('doctor.common.patient-tabs', ['active' => 'prescriptions'])

    <div class="glass-panel glass-panel--padded animate-unicare-scale-in stagger-2">
        @if ($prescriptions->isEmpty())
            <p class="text-sm opacity-75 py-4">No prescriptions found.</p>
        @else
            <table class="unicare-table">
                <thead>
                    <tr>
                        <th>Drug Name</th>
                        <th>Dosage</th>
                        <th>Frequency</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Issued At</th>
                        <th>Valid Until</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($prescriptions as $index => $prescription)
                        <tr class="animate-unicare-in stagger-{{ min($index + 1, 8) }}">
                            <td>{{ $prescription->drug_name }}</td>
                            <td>{{ $prescription->dosage }}</td>
                            <td>{{ $prescription->frequency }}</td>
                            <td>{{ $prescription->duration ?? '—' }}</td>
                            <td>
                                <span @class([
                                    'inline-block rounded-full px-3 py-1 text-xs font-medium capitalize',
                                    'bg-green-100 text-green-800' => $prescription->status === 'active',
                                    'bg-white/50' => $prescription->status !== 'active',
                                ])>
                                    {{ $prescription->status }}
                                </span>
                            </td>
                            <td>{{ $prescription->issued_at ?? '—' }}</td>
                            <td>{{ $prescription->valid_until ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
        <div class="pup-watermark"></div>
    </div>
@endsection
