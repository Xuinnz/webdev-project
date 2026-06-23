@extends('doctor.common.main')

@section('title', 'Onboarding')

@section('content')
    <h1 class="page-title animate-unicare-in stagger-1">Complete Your Profile</h1>
    <p class="mb-6 opacity-75 animate-unicare-in stagger-2">Set up your doctor profile and weekly schedule to get started.</p>

    <form
        action="{{ route('doctor.onboarding.store') }}"
        method="POST"
        class="animate-unicare-scale-in stagger-3"
        x-data="scheduleForm(@js(old('schedules', [['weekday' => 1, 'ranges' => [['start' => '08:00', 'end' => '12:00']]]])))"
    >
        @csrf

        <div class="glass-panel glass-panel--padded mb-6">
            <h2 class="section-title">Personal Information</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" id="phone" name="phone" class="form-input w-full" value="{{ old('phone') }}" required>
                    @error('phone')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="gender" class="form-label">Gender</label>
                    <select id="gender" name="gender" class="form-select w-full" required>
                        <option value="">Select gender</option>
                        @foreach (['male', 'female', 'other'] as $gender)
                            <option value="{{ $gender }}" @selected(old('gender') === $gender)>{{ ucfirst($gender) }}</option>
                        @endforeach
                    </select>
                    @error('gender')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mb-4">
                <label for="avatar_url" class="form-label">Avatar URL</label>
                <input type="url" id="avatar_url" name="avatar_url" class="form-input w-full" value="{{ old('avatar_url') }}" placeholder="https://...">
                @error('avatar_url')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="glass-panel glass-panel--padded mb-6">
            <h2 class="section-title">Professional Details</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="specialty_id" class="form-label">Specialty</label>
                    <select id="specialty_id" name="specialty_id" class="form-select w-full" required>
                        <option value="">Select specialty</option>
                        @foreach ($specialties as $specialty)
                            <option value="{{ $specialty->id }}" @selected(old('specialty_id') == $specialty->id)>
                                {{ $specialty->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('specialty_id')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="license_number" class="form-label">License Number</label>
                    <input type="text" id="license_number" name="license_number" class="form-input w-full" value="{{ old('license_number') }}" required>
                    @error('license_number')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mb-4">
                <label for="consultation_fee" class="form-label">Consultation Fee</label>
                <input type="number" id="consultation_fee" name="consultation_fee" class="form-input w-full" value="{{ old('consultation_fee') }}" step="0.01" min="0" required>
                @error('consultation_fee')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="mb-4">
                <label for="bio" class="form-label">Bio</label>
                <textarea id="bio" name="bio" class="form-textarea w-full" rows="4">{{ old('bio') }}</textarea>
                @error('bio')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="mb-6">
            <h2 class="section-title">Weekly Schedule</h2>
            @error('schedules')<p class="text-red-600 text-sm mb-2">{{ $message }}</p>@enderror
            @include('doctor.common.schedule-input')
        </div>

        <div class="btn-row">
            <button type="submit" class="unicare-btn-primary">Complete Setup</button>
        </div>
    </form>

    <details class="glass-panel glass-panel--padded mt-8 animate-unicare-in stagger-4">
        <summary class="cursor-pointer font-medium">Add a new specialty</summary>
        <form action="{{ route('doctor.specialty.store') }}" method="POST" class="mt-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="specialty_name" class="form-label">Specialty Name</label>
                    <input type="text" id="specialty_name" name="name" class="form-input w-full" required>
                </div>
                <div>
                    <label for="specialty_description" class="form-label">Description</label>
                    <input type="text" id="specialty_description" name="description" class="form-input w-full">
                </div>
            </div>
            <button type="submit" class="unicare-btn-primary">Add Specialty</button>
        </form>
    </details>
@endsection
