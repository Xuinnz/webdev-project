@extends('doctor.common.main')

@section('title', 'Records — ' . $patient->name)

@section('content')
    <div class="mb-4 text-sm opacity-75 animate-unicare-in stagger-1">
        <a href="{{ route('doctor.patients.index') }}" class="underline">Patients</a>
        <span> / </span>
        <a href="{{ route('doctor.patients.show', $patient->uuid) }}" class="underline">{{ $patient->name }}</a>
        <span> / Records</span>
    </div>

    <h1 class="page-title animate-unicare-in stagger-1">Medical Records</h1>

    @include('doctor.common.patient-tabs', ['active' => 'records'])

    <div class="glass-panel glass-panel--padded mb-6 animate-unicare-scale-in stagger-2">
        @if ($records->isEmpty())
            <p class="text-sm opacity-75 py-4">No medical records yet.</p>
        @else
            <table class="unicare-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Chief Complaint</th>
                        <th>Diagnosis</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($records as $index => $record)
                        <tr
                            class="is-clickable animate-unicare-in stagger-{{ min($index + 1, 8) }}"
                            onclick="window.location='{{ route('doctor.records.show', $record->uuid) }}'"
                        >
                            <td>{{ $record->record_date }}</td>
                            <td>{{ $record->chief_complaint ?? '—' }}</td>
                            <td>{{ $record->diagnosis ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="glass-panel glass-panel--padded animate-unicare-in stagger-3">
        <h2 class="section-title">Add Medical Record</h2>
        <form action="{{ route('doctor.patient.records.store', $patient->uuid) }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="record_date" class="form-label">Record Date</label>
                    <input type="date" id="record_date" name="record_date" class="form-input w-full" value="{{ old('record_date', now()->toDateString()) }}" required>
                    @error('record_date')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mb-4">
                <label for="chief_complaint" class="form-label">Chief Complaint</label>
                <textarea id="chief_complaint" name="chief_complaint" class="form-textarea w-full" rows="2">{{ old('chief_complaint') }}</textarea>
                @error('chief_complaint')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="mb-4">
                <label for="diagnosis" class="form-label">Diagnosis</label>
                <textarea id="diagnosis" name="diagnosis" class="form-textarea w-full" rows="2">{{ old('diagnosis') }}</textarea>
                @error('diagnosis')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="mb-6">
                <label for="notes" class="form-label">Notes</label>
                <textarea id="notes" name="notes" class="form-textarea w-full" rows="3">{{ old('notes') }}</textarea>
                @error('notes')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="unicare-btn-primary">Save Record</button>
        </form>
    </div>
@endsection
