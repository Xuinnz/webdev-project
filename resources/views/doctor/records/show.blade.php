@extends('doctor.common.main')

@section('title', 'Medical Record')

@section('content')
    <div class="mb-4 text-sm opacity-75 animate-unicare-in stagger-1">
        <a href="{{ route('doctor.patients.index') }}" class="underline">Patients</a>
        <span> / </span>
        <a href="{{ route('doctor.patients.show', $record->patient_uuid) }}" class="underline">{{ $record->patient_name }}</a>
        <span> / </span>
        <a href="{{ route('doctor.patient.records.index', $record->patient_uuid) }}" class="underline">Records</a>
        <span> / {{ $record->record_date }}</span>
    </div>

    <h1 class="page-title animate-unicare-in stagger-1">Medical Record</h1>

    <div class="glass-panel glass-panel--padded glass-panel--relative animate-unicare-scale-in stagger-2">
        <p class="drawer-date mb-2">{{ $record->record_date }}</p>
        <p class="mb-4 opacity-80">Patient: {{ $record->patient_name }}</p>

        <div class="drawer-details space-y-3 mb-6">
            <p><strong>Chief Complaint:</strong> {{ $record->chief_complaint ?? '—' }}</p>
            <p><strong>Diagnosis:</strong> {{ $record->diagnosis ?? '—' }}</p>
            @if ($record->notes)
                <p><strong>Notes:</strong> {{ $record->notes }}</p>
            @endif

            @if (!empty($record->vitals))
                <div>
                    <strong>Vitals:</strong>
                    <ul class="list-disc pl-5 mt-1">
                        @foreach ((array) $record->vitals as $key => $value)
                            <li>{{ ucfirst(str_replace('_', ' ', $key)) }}: {{ $value }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (!empty($record->attachments))
                <div>
                    <strong>Attachments:</strong>
                    <ul class="list-disc pl-5 mt-1">
                        @foreach ((array) $record->attachments as $attachment)
                            <li>
                                @if (is_string($attachment) && filter_var($attachment, FILTER_VALIDATE_URL))
                                    <a href="{{ $attachment }}" class="underline" target="_blank" rel="noopener">{{ $attachment }}</a>
                                @else
                                    {{ is_string($attachment) ? $attachment : json_encode($attachment) }}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <h2 class="section-title">Prescriptions</h2>
        @if ($record->prescriptions->isEmpty())
            <p class="opacity-75">—</p>
        @else
            <table class="unicare-table">
                <thead>
                    <tr>
                        <th>Drug</th>
                        <th>Dosage</th>
                        <th>Frequency</th>
                        <th>Duration</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($record->prescriptions as $prescription)
                        <tr>
                            <td>{{ $prescription->drug_name }}</td>
                            <td>{{ $prescription->dosage }}</td>
                            <td>{{ $prescription->frequency }}</td>
                            <td>{{ $prescription->duration ?? '—' }}</td>
                            <td class="capitalize">{{ $prescription->status }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="pup-watermark"></div>
    </div>

    <div class="mt-6">
        <a href="{{ route('doctor.patient.records.index', $record->patient_uuid) }}" class="unicare-btn-primary inline-block">
            Back to Records
        </a>
    </div>
@endsection
