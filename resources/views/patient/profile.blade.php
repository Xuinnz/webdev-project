@extends('patient.common.patientCommon')
@section('title', 'Profile')
@section('content')
    <h1 class="page-title animate-unicare-in stagger-1">Your Profile</h1>
    
    <form action="{{ route('patient.profile.update') }}" method="POST" class="glass-panel glass-panel--padded animate-unicare-scale-in stagger-2">
        @csrf
        
        <h2 class="text-lg font-bold mb-3 opacity-80">Account Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="name" class="form-label">Name</label>
                <input type="text" id="name" name="name" class="form-input w-full" value="{{ old('name', $profile->name) }}" required>
                @error('name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" class="form-input w-full opacity-60" value="{{ $profile->email }}" disabled>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label for="phone" class="form-label">Phone</label>
                <input type="text" id="phone" name="phone" class="form-input w-full" value="{{ old('phone', $profile->phone) }}" required>
                @error('phone')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="gender" class="form-label">Gender</label>
                <select id="gender" name="gender" class="form-select w-full" required>
                    <option value="">Select Gender</option>
                    @foreach (['male', 'female', 'other'] as $gender)
                        <option value="{{ $gender }}" @selected(old('gender', $profile->gender) === $gender)>{{ ucfirst($gender) }}</option>
                    @endforeach
                </select>
                @error('gender')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <h2 class="text-lg font-bold mb-3 opacity-80">Medical Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="date_of_birth" class="form-label">Date of Birth</label>
                <input type="date" id="date_of_birth" name="date_of_birth" class="form-input w-full" value="{{ old('date_of_birth', $profile->date_of_birth) }}">
                @error('date_of_birth')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="blood_type" class="form-label">Blood Type</label>
                <select id="blood_type" name="blood_type" class="form-select w-full">
                    <option value="">Select Blood Type</option>
                    @foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $type)
                        <option value="{{ $type }}" @selected(old('blood_type', $profile->blood_type) === $type)>{{ $type }}</option>
                    @endforeach
                </select>
                @error('blood_type')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label for="height_cm" class="form-label">Height (cm)</label>
                <input type="number" step="0.01" id="height_cm" name="height_cm" class="form-input w-full" value="{{ old('height_cm', $profile->height_cm) }}" placeholder="e.g., 175.5">
                @error('height_cm')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="weight_kg" class="form-label">Weight (kg)</label>
                <input type="number" step="0.01" id="weight_kg" name="weight_kg" class="form-input w-full" value="{{ old('weight_kg', $profile->weight_kg) }}" placeholder="e.g., 70.2">
                @error('weight_kg')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <h2 class="text-lg font-bold mb-3 opacity-80">Emergency Contact</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label for="emergency_contact_name" class="form-label">Contact Name</label>
                <input type="text" id="emergency_contact_name" name="emergency_contact_name" class="form-input w-full" value="{{ old('emergency_contact_name', $profile->emergency_contact_name) }}">
                @error('emergency_contact_name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="emergency_contact_phone" class="form-label">Contact Phone</label>
                <input type="text" id="emergency_contact_phone" name="emergency_contact_phone" class="form-input w-full" value="{{ old('emergency_contact_phone', $profile->emergency_contact_phone) }}">
                @error('emergency_contact_phone')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="mb-">
            <label for="avatar_url" class="form-label">Avatar URL</label>
            <input type="url" id="avatar_url" name="avatar_url" class="form-input w-full" value="{{ old('avatar_url', $profile->avatar_url) }}">
            @error('avatar_url')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        
            <button type="submit" class="unicare-btn-primary" style="margin-top: 1rem;">Save Profile</button>
    </form>
@endsection