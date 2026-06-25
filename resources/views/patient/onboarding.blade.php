@extends('patient.common.patientCommon')

@section('title', 'Onboarding')

@section('content')
    <h1 class="page-title animate-unicare-in stagger-1">Complete Your Profile</h1>
    <p class="mb-6 opacity-75 animate-unicare-in stagger-2">Tell us a bit about yourself so your doctors can provide better care.</p>

    <form
        action="{{ route('patient.onboarding.store') }}"
        method="POST"
        class="animate-unicare-scale-in stagger-3"
    >
        @csrf

        {{-- ── Personal Information ── --}}
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
                        @foreach (['male', 'female', 'other'] as $g)
                            <option value="{{ $g }}" @selected(old('gender') === $g)>{{ ucfirst($g) }}</option>
                        @endforeach
                    </select>
                    @error('gender')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="date_of_birth" class="form-label">Date of Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" class="form-input w-full" value="{{ old('date_of_birth') }}" required>
                    @error('date_of_birth')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="blood_type" class="form-label">Blood Type</label>
                    <select id="blood_type" name="blood_type" class="form-select w-full">
                        <option value="">Select blood type</option>
                        @foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bt)
                            <option value="{{ $bt }}" @selected(old('blood_type') === $bt)>{{ $bt }}</option>
                        @endforeach
                    </select>
                    @error('blood_type')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mb-4">
                <label for="avatar_url" class="form-label">Avatar URL</label>
                <input type="url" id="avatar_url" name="avatar_url" class="form-input w-full" value="{{ old('avatar_url') }}" placeholder="https://...">
                @error('avatar_url')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- ── Body Measurements ── --}}
        <div class="glass-panel glass-panel--padded mb-6">
            <h2 class="section-title">Body Measurements</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="height_cm" class="form-label">Height (cm)</label>
                    <input type="number" id="height_cm" name="height_cm" class="form-input w-full" value="{{ old('height_cm') }}" step="0.01" min="0" placeholder="e.g. 170">
                    @error('height_cm')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="weight_kg" class="form-label">Weight (kg)</label>
                    <input type="number" id="weight_kg" name="weight_kg" class="form-input w-full" value="{{ old('weight_kg') }}" step="0.01" min="0" placeholder="e.g. 65">
                    @error('weight_kg')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- ── Medical Background ── --}}
        <div class="glass-panel glass-panel--padded mb-6">
            <h2 class="section-title">Medical Background</h2>

            {{-- Allergies --}}
            <div class="mb-6" x-data="tagInput('allergies', @js(old('allergies', [])))">
                <label class="form-label">Allergies</label>
                <div class="flex gap-2 mb-2">
                    <input
                        type="text"
                        class="form-input w-full"
                        placeholder="e.g. Penicillin"
                        x-model="draft"
                        @keydown.enter.prevent="add()"
                        @keydown.comma.prevent="add()"
                    >
                    <button type="button" class="unicare-btn-primary" @click="add()">Add</button>
                </div>
                <div class="flex flex-wrap gap-2">
                    <template x-for="(tag, i) in tags" :key="i">
                        <span class="tag-pill">
                            <span x-text="tag"></span>
                            <button type="button" class="tag-pill__remove" @click="remove(i)" aria-label="Remove">×</button>
                            <input type="hidden" :name="`allergies[]`" :value="tag">
                        </span>
                    </template>
                </div>
                @error('allergies')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Chronic Conditions --}}
            <div x-data="tagInput('chronic_conditions', @js(old('chronic_conditions', [])))">
                <label class="form-label">Chronic Conditions</label>
                <div class="flex gap-2 mb-2">
                    <input
                        type="text"
                        class="form-input w-full"
                        placeholder="e.g. Type 2 Diabetes"
                        x-model="draft"
                        @keydown.enter.prevent="add()"
                        @keydown.comma.prevent="add()"
                    >
                    <button type="button" class="unicare-btn-primary" @click="add()">Add</button>
                </div>
                <div class="flex flex-wrap gap-2">
                    <template x-for="(tag, i) in tags" :key="i">
                        <span class="tag-pill">
                            <span x-text="tag"></span>
                            <button type="button" class="tag-pill__remove" @click="remove(i)" aria-label="Remove">×</button>
                            <input type="hidden" :name="`chronic_conditions[]`" :value="tag">
                        </span>
                    </template>
                </div>
                @error('chronic_conditions')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- ── Emergency Contact ── --}}
        <div class="glass-panel glass-panel--padded mb-6">
            <h2 class="section-title">Emergency Contact</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="emergency_contact_name" class="form-label">Contact Name</label>
                    <input type="text" id="emergency_contact_name" name="emergency_contact_name" class="form-input w-full" value="{{ old('emergency_contact_name') }}" placeholder="Full name">
                    @error('emergency_contact_name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="emergency_contact_phone" class="form-label">Contact Phone</label>
                    <input type="text" id="emergency_contact_phone" name="emergency_contact_phone" class="form-input w-full" value="{{ old('emergency_contact_phone') }}" placeholder="+63 9XX XXX XXXX">
                    @error('emergency_contact_phone')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="btn-row">
            <button type="submit" class="unicare-btn-primary">Complete Setup</button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    function tagInput(field, initial = []) {
        return {
            tags: Array.isArray(initial) ? initial : [],
            draft: '',
            add() {
                const val = this.draft.trim().replace(/,$/, '');
                if (val && !this.tags.includes(val)) {
                    this.tags.push(val);
                }
                this.draft = '';
            },
            remove(i) {
                this.tags.splice(i, 1);
            }
        };
    }
</script>
@endpush