@extends('doctor.common.main')

@section('title', 'Home')

@section('content')
    <div x-data="{
        isOpen: false,
        patient: {
            appointment_id: '',
            appointment_status: '',
            name: '',
            email: '',
            phone: '',
            gender: '',
            dob: '',
            blood_type: '',
            height: '',
            weight: '',
            allergies: [],
            chronic_conditions: [],
            emergency_contact_name: '',
            emergency_contact_phone: ''
        },
        open(data) {
            this.patient = {
                appointment_id: data.id || data.appointment_id || '',
                appointment_status: data.status || data.appointment_status || '',
                name: data.patient_name || '—',
                email: data.patient_email || '—',
                phone: data.patient_phone || '—',
                gender: data.patient_gender || '—',
                dob: data.patient_dob || '—',
                blood_type: data.patient_blood_type || '—',
                height: data.patient_height || '—',
                weight: data.patient_weight || '—',
                allergies: Array.isArray(data.patient_allergies) ? data.patient_allergies : [],
                chronic_conditions: Array.isArray(data.patient_chronic_conditions) ? data.patient_chronic_conditions : [],
                emergency_contact_name: data.patient_emergency_contact_name || '—',
                emergency_contact_phone: data.patient_emergency_contact_phone || '—'
            };
            this.isOpen = true;
        },
        close() {
            this.isOpen = false;
        }
    }">
        @include('doctor.common.weekly-calendar', [
            'weekDays' => $weekDays,
            'calendarAppointments' => $calendarAppointments,
            'calendarHours' => $calendarHours,
        ])

        <!-- Patient Info Modal -->
        <div
            x-show="isOpen"
            x-cloak
            class="doctor-encounter-modal"
            @keydown.escape.window="close()"
        >
            <div class="doctor-encounter-modal__backdrop" @click="close()"></div>
            <div class="doctor-encounter-modal__panel glass-panel glass-panel--padded" @click.stop style="max-width: 34rem;">
                <button type="button" class="drawer-close" @click="close()" style="border: none; background: none; font-size: 1.75rem; color: var(--text-faint); cursor: pointer; position: absolute; top: 1rem; right: 1.25rem;">&times;</button>
                
                <div style="margin-bottom: 1.5rem;">
                    <h3 class="section-title" style="margin-bottom: 0.25rem; font-size: 1.5rem; color: var(--text-primary);" x-text="patient.name"></h3>
                    <p class="text-sm" style="color: var(--text-muted); font-size: 0.875rem;">Patient Information</p>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1.5rem;">
                    <div>
                        <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; color: var(--text-faint); display: block; margin-bottom: 0.25rem;">Gender</span>
                        <span style="font-size: 0.875rem; font-weight: 500; color: var(--text-primary);" x-text="patient.gender"></span>
                    </div>
                    <div>
                        <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; color: var(--text-faint); display: block; margin-bottom: 0.25rem;">Date of Birth</span>
                        <span style="font-size: 0.875rem; font-weight: 500; color: var(--text-primary);" x-text="patient.dob"></span>
                    </div>
                    <div>
                        <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; color: var(--text-faint); display: block; margin-bottom: 0.25rem;">Email Address</span>
                        <span style="font-size: 0.875rem; font-weight: 500; color: var(--text-primary);" x-text="patient.email"></span>
                    </div>
                    <div>
                        <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; color: var(--text-faint); display: block; margin-bottom: 0.25rem;">Phone Number</span>
                        <span style="font-size: 0.875rem; font-weight: 500; color: var(--text-primary);" x-text="patient.phone"></span>
                    </div>
                    <div>
                        <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; color: var(--text-faint); display: block; margin-bottom: 0.25rem;">Blood Type</span>
                        <span style="font-size: 0.875rem; font-weight: 500; color: var(--text-primary);" x-text="patient.blood_type"></span>
                    </div>
                    <div>
                        <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; color: var(--text-faint); display: block; margin-bottom: 0.25rem;">Height & Weight</span>
                        <span style="font-size: 0.875rem; font-weight: 500; color: var(--text-primary);" x-text="patient.height + ' / ' + patient.weight"></span>
                    </div>
                </div>

                <div style="border-top: 1px solid rgba(0, 0, 0, 0.1); padding-top: 1.25rem; margin-bottom: 1.5rem;">
                    <div style="margin-bottom: 1.25rem;">
                        <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; color: var(--text-faint); display: block; margin-bottom: 0.5rem;">Allergies</span>
                        <template x-if="patient.allergies.length === 0">
                            <span style="font-size: 0.875rem; color: var(--text-muted);">None reported</span>
                        </template>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                            <template x-for="allergy in patient.allergies">
                                <span style="background: rgba(196, 30, 58, 0.1); color: var(--unicare-red); padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;" x-text="allergy"></span>
                            </template>
                        </div>
                    </div>
                    <div>
                        <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; color: var(--text-faint); display: block; margin-bottom: 0.5rem;">Chronic Conditions</span>
                        <template x-if="patient.chronic_conditions.length === 0">
                            <span style="font-size: 0.875rem; color: var(--text-muted);">None reported</span>
                        </template>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                            <template x-for="condition in patient.chronic_conditions">
                                <span style="background: rgba(109, 140, 184, 0.15); color: var(--unicare-navy); padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;" x-text="condition"></span>
                            </template>
                        </div>
                    </div>
                </div>

                <div style="border-top: 1px solid rgba(0, 0, 0, 0.1); padding-top: 1.25rem;">
                    <h4 style="font-size: 0.875rem; font-weight: 700; margin-bottom: 0.75rem; color: var(--text-primary);">Emergency Contact</h4>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                        <div>
                            <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; color: var(--text-faint); display: block; margin-bottom: 0.25rem;">Contact Name</span>
                            <span style="font-size: 0.875rem; font-weight: 500; color: var(--text-primary);" x-text="patient.emergency_contact_name"></span>
                        </div>
                        <div>
                            <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; color: var(--text-faint); display: block; margin-bottom: 0.25rem;">Phone Number</span>
                            <span style="font-size: 0.875rem; font-weight: 500; color: var(--text-primary);" x-text="patient.emergency_contact_phone"></span>
                        </div>
                    </div>
                </div>

                {{-- Action Footer: Confirm / Cancel Appointment --}}
                <div style="border-top: 1px solid rgba(0, 0, 0, 0.1); padding-top: 1.25rem; margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
                    <button type="button" class="unicare-btn-ghost" @click="close()">Close</button>
                    
                    {{-- Cancel Button (Shows for both pending and confirmed) --}}
                    <template x-if="patient.appointment_status === 'pending'">
                        <form x-bind:action="'/doctor/appointments/' + patient.appointment_id + '/cancel'" method="POST" style="margin: 0;">
                            @csrf
                            <button type="submit" class="unicare-btn-danger">Cancel Appointment</button>
                        </form>
                    </template>

                    {{-- Confirm Button (Shows only for pending) --}}
                    <template x-if="patient.appointment_status === 'pending'">
                        <form x-bind:action="'/doctor/appointments/' + patient.appointment_id + '/confirm'" method="POST" style="margin: 0;">
                            @csrf
                            <button type="submit" class="unicare-btn-primary">Confirm Appointment</button>
                        </form>
                    </template>
                </div>

            </div>
        </div>
    </div>
@endsection