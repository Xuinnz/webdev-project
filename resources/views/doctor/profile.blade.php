@extends('doctor.common.main')

@section('title', 'Profile')

@section('content')
    <h1 class="page-title animate-unicare-in stagger-1">Your Profile</h1>

    <form action="{{ route('doctor.profile.update') }}" method="POST" class="glass-panel glass-panel--padded animate-unicare-scale-in stagger-2">
        @csrf

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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="phone" class="form-label">Phone</label>
                <input type="text" id="phone" name="phone" class="form-input w-full" value="{{ old('phone', $profile->phone) }}" required>
                @error('phone')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="gender" class="form-label">Gender</label>
                <select id="gender" name="gender" class="form-select w-full" required>
                    @foreach (['male', 'female', 'other'] as $gender)
                        <option value="{{ $gender }}" @selected(old('gender', $profile->gender) === $gender)>{{ ucfirst($gender) }}</option>
                    @endforeach
                </select>
                @error('gender')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="mb-4">
            <label for="avatar_url" class="form-label">Avatar URL</label>
            <input type="url" id="avatar_url" name="avatar_url" class="form-input w-full" value="{{ old('avatar_url', $profile->avatar_url) }}">
            @error('avatar_url')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="specialty_id" class="form-label">Specialty</label>
                <select id="specialty_id" name="specialty_id" class="form-select w-full" required>
                    @foreach ($specialties as $specialty)
                        <option value="{{ $specialty->id }}" @selected(old('specialty_id', $profile->specialty_id) == $specialty->id)>
                            {{ $specialty->name }}
                        </option>
                    @endforeach
                </select>
                @error('specialty_id')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="license_number" class="form-label">License Number</label>
                <input type="text" id="license_number" class="form-input w-full opacity-60" value="{{ $profile->license_number }}" disabled>
            </div>
        </div>

        <div class="mb-4">
            <label for="consultation_fee" class="form-label">Consultation Fee</label>
            <input type="number" id="consultation_fee" name="consultation_fee" class="form-input w-full" value="{{ old('consultation_fee', $profile->consultation_fee) }}" step="0.01" min="0" required>
            @error('consultation_fee')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-6">
            <label for="bio" class="form-label">Bio</label>
            <textarea id="bio" name="bio" class="form-textarea w-full" rows="4">{{ old('bio', $profile->bio) }}</textarea>
            @error('bio')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="unicare-btn-primary">Save Profile</button>
    </form>
@endsection
