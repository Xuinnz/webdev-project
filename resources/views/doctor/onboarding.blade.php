@extends('doctor.common.main')

@section('title', 'Onboarding')

@section('content')
<div class="unicare-brand animate-unicare-in stagger-1" style="margin-bottom: 2rem;">
    <div>
        <h1 class="page-title" style="margin-bottom: 0.5rem;">Complete Your Profile</h1>
        <p class="opacity-75" style="margin: 0; font-size: 1rem;">Set up your doctor profile and weekly schedule to get started.</p>
    </div>
</div>

<form
    action="{{ route('doctor.onboarding.store') }}"
    method="POST"
    class="animate-unicare-scale-in stagger-3"
    x-data="onboardingForm(@js($slots), {{ old('slot_duration_minutes', 30) }})"
    @submit="prepareSubmit"
>
    @csrf
    <div class="glass-panel glass-panel--padded mb-6">
        <h2 class="section-title">Personal Information</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="phone" class="form-label">Phone</label>
                <input type="text" id="phone" name="phone" class="form-input w-full"
                    value="{{ old('phone') }}" required>
                @error('phone')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="gender" class="form-label">Gender</label>
                <select id="gender" name="gender" class="form-select w-full" required>
                    <option value="">Select gender</option>
                    @foreach (['male', 'female', 'other'] as $g)
                        <option value="{{ $g }}" @selected(old('gender') === $g)>{{ ucfirst($g) }}</option>
                    @endforeach
                </select>
                @error('gender')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="mb-4">
            <label for="avatar_url" class="form-label">Avatar URL</label>
            <input type="url" id="avatar_url" name="avatar_url" class="form-input w-full"
                value="{{ old('avatar_url') }}" placeholder="https://...">
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
                <input type="text" id="license_number" name="license_number" class="form-input w-full"
                    value="{{ old('license_number') }}" required>
                @error('license_number')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="consultation_fee" class="form-label">Consultation Fee (₱)</label>
                <input type="number" id="consultation_fee" name="consultation_fee"
                    class="form-input w-full" value="{{ old('consultation_fee') }}"
                    step="0.01" min="0" required>
                @error('consultation_fee')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="form-label">Appointment Duration</label>
                <div class="grid grid-cols-3 gap-2 mt-1">
                    @foreach ($durations as $d)
                        <button
                            type="button"
                            @click="setDuration({{ $d['value'] }})"
                            :class="duration === {{ $d['value'] }}
                                ? 'unicare-btn-primary text-sm py-2'
                                : 'unicare-btn-ghost text-sm py-2'"
                        >{{ $d['label'] }}</button>
                    @endforeach
                </div>
                <p class="text-xs opacity-60 mt-2" x-text="durationHint"></p>
                @error('slot_duration_minutes')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label for="bio" class="form-label">Bio</label>
            <textarea id="bio" name="bio" class="form-textarea w-full" rows="4">{{ old('bio') }}</textarea>
            @error('bio')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="glass-panel glass-panel--padded mb-6">
        <h2 class="section-title mb-1">Weekly Schedule</h2>
        <p class="text-sm opacity-60 mb-5">Enable days you're available, then tap the time slots you want to open.</p>

        @error('schedules')<p class="text-red-600 text-sm mb-3">{{ $message }}</p>@enderror

        <div class="grid grid-cols-7 gap-3 mb-10">
            <template x-for="day in days" :key="day.weekday">
                <button
                    type="button"
                    @click="toggleDay(day.weekday)"
                    :class="day.enabled
                        ? 'unicare-btn-primary text-sm py-2 px-1 text-center'
                        : 'unicare-btn-ghost text-sm py-2 px-1 text-center opacity-50'"
                    x-text="day.short"
                ></button>
            </template>
        </div>

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

                <div class="flex flex-wrap gap-x-3 gap-y-4">
                    <template x-for="slot in visibleSlots" :key="slot.start">
                        <button
                            type="button"
                            :class="isSlotSelected(day.weekday, slot.start)
                                ? 'schedule-slot schedule-slot--active'
                                : 'schedule-slot'"
                            @click="toggleSlot(day.weekday, slot.start)"
                            x-text="slot.start"
                        ></button>
                    </template>
                </div>

                <p class="text-xs opacity-50 mt-2"
                    x-text="selectedSlotCount(day.weekday) + ' slot(s) · ' + selectedHours(day.weekday) + ' hrs total'">
                </p>
            </div>
        </template>

        <template x-if="!days.some(d => d.enabled)">
            <p class="text-sm opacity-50 text-center py-6">Enable at least one day above to set your schedule.</p>
        </template>
    </div>

    <div id="schedule-hidden-inputs"></div>
    <input type="hidden" name="slot_duration_minutes" :value="duration">

    <div class="btn-row">
        <button type="submit" class="unicare-btn-primary">Complete Setup</button>
    </div>
</form>

<details class="glass-panel glass-panel--padded mt-8 animate-unicare-in stagger-4">
    <summary class="cursor-pointer section-title" style="margin: 0; user-select: none;">Add a new specialty</summary>
    <form action="{{ route('doctor.specialty.store') }}" method="POST" class="mt-4 pt-4" style="border-top: 1px solid rgba(0,0,0,0.1);">
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