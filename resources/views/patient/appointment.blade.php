@extends('patient.common.patientCommon')

@section('title', 'Appointments')

@section('content')
    <div x-data="appointmentActions(@js($doctors))">
        <h1 class="page-title animate-unicare-in stagger-1">Your Appointments</h1>

        <div class="glass-panel glass-panel--padded glass-panel--relative animate-unicare-scale-in stagger-2">
            <table class="unicare-table">
                <thead>
                    <tr>
                        <th>Doctor</th>
                        <th>Type</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Status</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($appointments as $index => $appointment)
                        <tr class="animate-unicare-in stagger-{{ min($index + 1, 8) }}">
                            <td>{{ $appointment['doctor_name'] }}</td>
                            <td>{{ $appointment['type_label'] }}</td>
                            <td>{{ $appointment['start_time'] }}</td>
                            <td>{{ $appointment['end_time'] }}</td>
                            <td>{{ $appointment['status_label'] }}</td>
                            <td>{{ $appointment['reason'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="pup-watermark"></div>
        </div>

        <div class="btn-row animate-unicare-in stagger-5">
            <button type="button" class="unicare-btn-primary" @click="openSchedule()">
                Schedule an Appointment
            </button>
            <button type="button" class="unicare-btn-danger" @click="openCancel()">
                Cancel an Appointment
            </button>
        </div>

        {{-- Schedule modal: doctor directory + booking form --}}
        <div
            x-show="showSchedule"
            x-cloak
            x-transition:enter="backdrop-enter"
            x-transition:enter-start="backdrop-enter-start"
            x-transition:enter-end="backdrop-enter-end"
            x-transition:leave="backdrop-leave"
            x-transition:leave-start="backdrop-leave-start"
            x-transition:leave-end="backdrop-leave-end"
            class="schedule-modal-backdrop"
            @keydown.escape.window="closePanels()"
        >
            <div
                x-show="showSchedule"
                x-transition:enter="modal-enter"
                x-transition:enter-start="modal-enter-start"
                x-transition:enter-end="modal-enter-end"
                x-transition:leave="modal-leave"
                x-transition:leave-start="modal-leave-start"
                x-transition:leave-end="modal-leave-end"
                class="schedule-modal"
                @click.outside="closePanels()"
            >
                <button type="button" class="schedule-modal-close" @click="closePanels()">×</button>

                {{-- Step 1: Doctor directory --}}
                <div x-show="!selectedDoctor" x-cloak>
                    <h2 class="schedule-modal-title animate-unicare-in">UniCare Doctors</h2>

                    <input
                        type="search"
                        class="doctor-search animate-unicare-in stagger-1"
                        placeholder="Search name or specialty"
                        x-model="search"
                    >

                    <div class="doctor-grid">
                        <template x-for="(doctor, index) in filteredDoctors" :key="doctor.id">
                            <button
                                type="button"
                                class="doctor-card animate-unicare-in"
                                :class="doctor.theme === 'dark' ? 'doctor-card--dark' : 'doctor-card--light'"
                                x-bind:class="'stagger-' + Math.min((index % 8) + 2, 8)"
                                @click="selectDoctor(doctor)"
                            >
                                <h3 class="doctor-card-name" x-text="doctor.name"></h3>
                                <p class="doctor-card-specialty" x-text="doctor.specialty"></p>
                                <p class="doctor-card-bio" x-text="doctor.bio"></p>
                            </button>
                        </template>
                    </div>

                    <p x-show="filteredDoctors.length === 0" class="doctor-empty animate-unicare-in">
                        No doctors match your search.
                    </p>
                </div>

                {{-- Step 2: Appointment form --}}
                <div x-show="selectedDoctor" x-cloak>
                    <button type="button" class="booking-back animate-unicare-in" @click="backToDoctors()">
                        ← Back to doctors
                    </button>

                    <div class="booking-header animate-unicare-in stagger-1">
                        <h2 class="schedule-modal-title" x-text="selectedDoctor?.name"></h2>
                        <p class="booking-specialty" x-text="selectedDoctor?.specialty"></p>
                    </div>

                    <section class="booking-section animate-unicare-in stagger-2">
                        <h3 class="booking-label">Availability (next 7 days)</h3>

                        <template x-for="day in selectedDoctor?.availability ?? []" :key="day.appointment_date">
                            <div class="availability-day">
                                <p class="availability-date" x-text="day.label"></p>
                                <div class="availability-slots">
                                    <template x-for="slot in day.slots" :key="day.appointment_date + slot">
                                        <button
                                            type="button"
                                            class="availability-slot"
                                            :class="{ 'is-selected': isSlotSelected(day.appointment_date, slot) }"
                                            @click="selectSlot(day.appointment_date, slot)"
                                            x-text="slot"
                                        ></button>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </section>

                    <section class="booking-section animate-unicare-in stagger-3">
                        <label class="form-label" for="appointment-reason">Reason for appointment</label>
                        <textarea
                            id="appointment-reason"
                            class="form-textarea"
                            rows="3"
                            placeholder="Describe your symptoms or reason for visit..."
                            x-model="reason"
                        ></textarea>
                    </section>

                    <section class="booking-section animate-unicare-in stagger-4">
                        <p class="form-label">Type of appointment</p>
                        <div class="appointment-type-group">
                            <label class="appointment-type-option">
                                <input type="radio" name="appointment_type" value="in_person" x-model="appointmentType">
                                <span>In-Person</span>
                            </label>
                            <label class="appointment-type-option">
                                <input type="radio" name="appointment_type" value="telemedicine" x-model="appointmentType">
                                <span>Telemedicine</span>
                            </label>
                        </div>
                    </section>

                    <button
                        type="button"
                        class="unicare-btn-primary booking-submit animate-unicare-in stagger-5"
                        :disabled="!canSubmit"
                        @click="submitBooking()"
                    >
                        Confirm Appointment
                    </button>
                </div>
            </div>
        </div>

        {{-- Cancel panel --}}
        <div
            x-show="showCancel"
            x-cloak
            x-transition:enter="panel-enter"
            x-transition:enter-start="panel-enter-start"
            x-transition:enter-end="panel-enter-end"
            x-transition:leave="panel-leave"
            x-transition:leave-start="panel-leave-start"
            x-transition:leave-end="panel-leave-end"
            class="action-panel"
        >
            <div class="action-panel-header">
                <h2 class="action-panel-title">Cancel an Appointment</h2>
                <button type="button" class="action-panel-close" @click="closePanels()">×</button>
            </div>
            <p class="action-panel-text">Select the appointment you want to cancel.</p>
            <form action="{{ route('patient.appointments.cancel') }}" method="POST">
            @csrf
                <select name="appointment_id" class="form-select mb-4" required>
                    <option value="">Select an appointment...</option>
                    
                    @foreach ($appointments as $app)
                        @if ($app['is_cancellable'])
                            <option value="{{ $app['id'] }}">
                                {{ $app['doctor_name'] }} — {{ $app['start_time'] }} ({{ $app['status_label'] }})
                            </option>
                        @endif
                    @endforeach
                    
                </select>
                <button type="submit" class="unicare-btn-danger w-full" @click="closePanels()">Confirm Cancel</button>
            </form>
        </div>
    </div>
@endsection
