@php
    $active = $active ?? 'overview';
@endphp

<nav class="flex flex-wrap gap-2 mb-6 animate-unicare-in stagger-2">
    <a
        href="{{ route('doctor.patients.show', $patient->uuid) }}"
        @class([
            'unicare-btn-primary text-sm py-2 px-4',
            'opacity-100' => $active === 'overview',
            'opacity-60' => $active !== 'overview',
        ])
    >
        Overview
    </a>
    <a
        href="{{ route('doctor.patient.records.index', $patient->uuid) }}"
        @class([
            'unicare-btn-primary text-sm py-2 px-4',
            'opacity-100' => $active === 'records',
            'opacity-60' => $active !== 'records',
        ])
    >
        Records
    </a>
    <a
        href="{{ route('doctor.patient.prescriptions.index', $patient->uuid) }}"
        @class([
            'unicare-btn-primary text-sm py-2 px-4',
            'opacity-100' => $active === 'prescriptions',
            'opacity-60' => $active !== 'prescriptions',
        ])
    >
        Prescriptions
    </a>
</nav>
