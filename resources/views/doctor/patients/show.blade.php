@extends('doctor.common.main')

@section('title', $patient->name)

@section('content')
    <div class="mb-4 text-sm opacity-75 animate-unicare-in stagger-1">
        <a href="{{ route('doctor.patients.index') }}" class="underline">Patients</a>
        <span> / {{ $patient->name }}</span>
    </div>

    <div class="unicare-card-dark p-6 mb-6 animate-unicare-scale-in stagger-2">
        <div class="flex flex-wrap items-start gap-4">
            @if ($patient->avatar_url)
                <img src="{{ $patient->avatar_url }}" alt="" class="w-16 h-16 rounded-full object-cover">
            @endif
            <div>
                <h1 class="page-title mb-2">{{ $patient->name }}</h1>
                <p class="opacity-80">{{ $patient->email }}</p>
                <p class="opacity-80">{{ $patient->phone ?? '—' }} · {{ ucfirst($patient->gender ?? '—') }}</p>
                @if ($patient->date_of_birth)
                    <p class="opacity-80 mt-1">DOB: {{ $patient->date_of_birth }}</p>
                @endif
                @if ($patient->blood_type)
                    <p class="opacity-80">Blood type: {{ $patient->blood_type }}</p>
                @endif
            </div>
        </div>
        <div class="pup-watermark"></div>
    </div>

    @include('doctor.common.patient-tabs', ['active' => 'overview'])

    <div class="glass-panel glass-panel--padded animate-unicare-in stagger-3">
        <h2 class="section-title">Medical Profile</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <p><strong>Height:</strong> {{ $patient->height_cm ? $patient->height_cm . ' cm' : '—' }}</p>
            <p><strong>Weight:</strong> {{ $patient->weight_kg ? $patient->weight_kg . ' kg' : '—' }}</p>
        </div>

        <div class="mb-4">
            <strong>Allergies:</strong>
            @if (!empty($patient->allergies))
                <ul class="list-disc pl-5 mt-1">
                    @foreach ((array) $patient->allergies as $allergy)
                        <li>{{ is_string($allergy) ? $allergy : json_encode($allergy) }}</li>
                    @endforeach
                </ul>
            @else
                <span>—</span>
            @endif
        </div>

        <div class="mb-4">
            <strong>Chronic Conditions:</strong>
            @if (!empty($patient->chronic_conditions))
                <ul class="list-disc pl-5 mt-1">
                    @foreach ((array) $patient->chronic_conditions as $condition)
                        <li>{{ is_string($condition) ? $condition : json_encode($condition) }}</li>
                    @endforeach
                </ul>
            @else
                <span>—</span>
            @endif
        </div>

        <div>
            <strong>Emergency Contact:</strong>
            @if ($patient->emergency_contact_name)
                <p class="mt-1">{{ $patient->emergency_contact_name }} — {{ $patient->emergency_contact_phone ?? '—' }}</p>
            @else
                <span>—</span>
            @endif
        </div>
    </div>
@endsection
