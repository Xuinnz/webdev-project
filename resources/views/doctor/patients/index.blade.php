@extends('doctor.common.main')

@section('title', 'Patients')

@section('content')
    <h1 class="page-title animate-unicare-in stagger-1">Patients</h1>

    <div class="glass-panel glass-panel--padded animate-unicare-scale-in stagger-2">
        @if ($patients->isEmpty())
            <p class="text-sm opacity-75 py-4">No patients found.</p>
        @else
            <table class="unicare-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Last Visit</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($patients as $index => $patient)
                        <tr class="animate-unicare-in stagger-{{ min($index + 1, 8) }}">
                            <td>
                                <div class="flex items-center gap-2">
                                    @if ($patient->avatar_url)
                                        <img src="{{ $patient->avatar_url }}" alt="" class="w-8 h-8 rounded-full object-cover">
                                    @endif
                                    {{ $patient->name }}
                                </div>
                            </td>
                            <td>{{ $patient->email }}</td>
                            <td>{{ $patient->phone ?? '—' }}</td>
                            <td>{{ $patient->last_visit ?? '—' }}</td>
                            <td>
                                <a href="{{ route('doctor.patients.show', $patient->uuid) }}" class="text-sm underline">
                                    View
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
