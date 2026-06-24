@extends('doctor.common.main')

@section('title', 'Profile')

@section('content')
    <h1 class="page-title animate-unicare-in stagger-1">Your Profile</h1>

    <form
        action="{{ route('doctor.profile.update') }}"
        method="POST"
        x-data="onboardingForm(@js($slots), {{ $duration }}, @js($existingSchedule))"
        @submit="prepareSubmit"
    >
        @csrf

        {{-- ── Personal Information ── --}}
        <div class="glass-panel glass-panel--padded mb-6 animate-unicare-scale-in stagger-2">
            <h2 class="section-title">Personal Information</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="name" class="form-label">Name</label>
                    <input type="text" id="name" name="name" class="form-input w-full"
                        value="{{ old('name', $profile->name) }}" required>
                    @error('name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" class="form-input w-full opacity-60"
                        value="{{ $profile->email }}" disabled>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" id="phone" name="phone" class="form-input w-full"
                        value="{{ old('phone', $profile->phone) }}" required>
                    @error('phone')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="gender" class="form-label">Gender</label>
                    <select id="gender" name="gender" class="form-select w-full" required>
                        @foreach (['male', 'female', 'other'] as $g)
                            <option value="{{ $g }}" @selected(old('gender', $profile->gender) === $g)>
                                {{ ucfirst($g) }}
                            </option>
                        @endforeach
                    </select>
                    @error('gender')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="avatar_url" class="form-label">Avatar URL</label>
                <input type="url" id="avatar_url" name="avatar_url" class="form-input w-full"
                    value="{{ old('avatar_url', $profile->avatar_url) }}" placeholder="https://...">
                @error('avatar_url')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- ── Professional Details ── --}}
        <div class="glass-panel glass-panel--padded mb-6 animate-unicare-scale-in stagger-3">
            <h2 class="section-title">Professional Details</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="specialty_id" class="form-label">Specialty</label>
                    <select id="specialty_id" name="specialty_id" class="form-select w-full" required>
                        @foreach ($specialties as $specialty)
                            <option value="{{ $specialty->id }}"
                                @selected(old('specialty_id', $profile->specialty_id) == $specialty->id)>
                                {{ $specialty->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('specialty_id')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="license_number" class="form-label">License Number</label>
                    <input type="text" id="license_number" class="form-input w-full opacity-60"
                        value="{{ $profile->license_number }}" disabled>
                </div>
            </div>

            <div class="mb-4">
                <label for="consultation_fee" class="form-label">Consultation Fee</label>
                <input type="number" id="consultation_fee" name="consultation_fee"
                    class="form-input w-full"
                    value="{{ old('consultation_fee', $profile->consultation_fee) }}"
                    step="0.01" min="0" required>
                @error('consultation_fee')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Slot Duration --}}
            <div class="mb-4">
                <label class="form-label">Appointment Duration</label>
                <div class="grid grid-cols-3 md:grid-cols-6 gap-2 mt-1">
                    @foreach ($durations as $opt)
                        <button
                            type="button"
                            @click="setDuration({{ $opt['value'] }})"
                            :class="duration === {{ $opt['value'] }}
                                ? 'unicare-btn-primary text-sm py-2'
                                : 'unicare-btn-ghost text-sm py-2'"
                        >
                            {{ $opt['label'] }}
                        </button>
                    @endforeach
                </div>
                <p class="text-xs opacity-60 mt-2" x-text="durationHint"></p>
                @error('slot_duration_minutes')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="bio" class="form-label">Bio</label>
                <textarea id="bio" name="bio" class="form-textarea w-full" rows="4">{{ old('bio', $profile->bio) }}</textarea>
                @error('bio')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- ── Weekly Schedule ── --}}
        <div class="glass-panel glass-panel--padded mb-6 animate-unicare-scale-in stagger-4">
            <h2 class="section-title mb-1">Weekly Schedule</h2>
            <p class="text-sm opacity-60 mb-5">Toggle days and select your available time slots.</p>

            @error('schedules')<p class="text-red-600 text-sm mb-3">{{ $message }}</p>@enderror

            {{-- Day toggles --}}
            <div class="grid grid-cols-7 gap-2 mb-6">
                <template x-for="day in days" :key="day.weekday">
                    <button
                        type="button"
                        @click="toggleDay(day.weekday)"
                        :class="day.enabled
                            ? 'unicare-btn-primary text-sm py-2 px-1 text-center'
                            : 'unicare-btn-ghost text-sm py-2 px-1 text-center opacity-50'"
                    >
                        <span x-text="day.short"></span>
                    </button>
                </template>
            </div>

            {{-- Slot grid per enabled day --}}
            <template x-for="day in days" :key="day.weekday">
                <div x-show="day.enabled" x-transition class="mb-6">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-medium" x-text="day.label"></h3>
                        <div class="flex gap-2">
                            <button type="button" @click="selectAll(day.weekday)"
                                class="text-xs underline opacity-60 hover:opacity-100">Select all</button>
                            <span class="opacity-30">·</span>
                            <button type="button" @click="clearAll(day.weekday)"
                                class="text-xs underline opacity-60 hover:opacity-100">Clear</button>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="slot in visibleSlots" :key="slot.start">
                            <button
                                type="button"
                                @click="toggleSlot(day.weekday, slot.start)"
                                :class="isSlotSelected(day.weekday, slot.start)
                                    ? 'schedule-slot schedule-slot--active'
                                    : 'schedule-slot'"
                                x-text="slot.label"
                            ></button>
                        </template>
                    </div>
                    <p class="text-xs opacity-50 mt-2"
                        x-text="selectedSlotCount(day.weekday) + ' slot(s) · ' + selectedHours(day.weekday) + ' hrs'">
                    </p>
                </div>
            </template>

            <template x-if="!days.some(d => d.enabled)">
                <p class="text-sm opacity-50 text-center py-6">Enable at least one day to set your schedule.</p>
            </template>
        </div>

        {{-- Hidden schedule inputs injected by Alpine on submit --}}
        <div id="schedule-hidden-inputs"></div>
        <input type="hidden" name="slot_duration_minutes" :value="duration">

        <div class="btn-row">
            <button type="submit" class="unicare-btn-primary">Save Profile</button>
        </div>
    </form>
@endsection