@extends('doctor.common.main')

@section('title', 'Schedule')

@section('content')
    <h1 class="page-title animate-unicare-in stagger-1">Your Schedule</h1>
    <p class="mb-6 opacity-75 animate-unicare-in stagger-2">Set your weekly availability for patient appointments.</p>

    <form
        action="{{ route('doctor.schedule.update') }}"
        method="POST"
        x-data="scheduleForm(@js(old('schedules', $schedules)))"
        class="animate-unicare-scale-in stagger-3"
    >
        @csrf

        @error('schedules')<p class="text-red-600 text-sm mb-4">{{ $message }}</p>@enderror

        @include('doctor.common.schedule-input')

        <div class="btn-row mt-6">
            <button type="submit" class="unicare-btn-primary">Update Schedule</button>
        </div>
    </form>
@endsection
