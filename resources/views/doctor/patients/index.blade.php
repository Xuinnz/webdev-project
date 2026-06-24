@extends('doctor.common.main')

@section('title', 'Patients')

@section('content')
<div x-data="encounterEdit()">

    {{-- ── Today's Patients ── --}}
    <section class="section animate-unicare-in stagger-1">
        <h2 class="section-title">Today's Patients</h2>
        <div class="glass-panel glass-panel--padded doctor-patients-panel doctor-patients-panel--light">
            @if ($todayPatients->isEmpty())
                <p class="text-sm opacity-75 py-4">No patients scheduled for today.</p>
            @else
                <table class="unicare-table doctor-patients-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Complaint</th>
                            <th>Diagnosis</th>
                            <th>Prescriptions</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($todayPatients as $index => $row)
                            <tr class="animate-unicare-in stagger-{{ min($index + 1, 8) }}">
                                <td>{{ $row->patient_name }}</td>
                                <td>{{ $row->type_label }}</td>
                                <td>{{ $row->start_time }}</td>
                                <td>{{ $row->end_time }}</td>
                                <td>{{ $row->chief_complaint ?? '—' }}</td>
                                <td>{{ $row->diagnosis ?? '—' }}</td>
                                <td>{{ $row->prescription_summary ?? '—' }}</td>
                                <td>
                                    {{-- Data stored in attributes to avoid inline JS object issues --}}
                                    <button
                                        type="button"
                                        class="doctor-edit-btn"
                                        title="Edit encounter"
                                        data-uuid="{{ $row->uuid }}"
                                        data-complaint="{{ $row->chief_complaint ?? '' }}"
                                        data-diagnosis="{{ $row->diagnosis ?? '' }}"
                                        data-notes="{{ $row->notes ?? '' }}"
                                        data-vitals="{{ $row->vitals ?? '{}' }}"
                                        data-prescriptions="{{ $row->prescriptions ?? '[]' }}"
                                        @click="openFromEl($event.currentTarget)"
                                    >&#9998;</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </section>

    {{-- ── Upcoming Patients ── --}}
    <section class="section animate-unicare-in stagger-2">
        <h2 class="section-title">Upcoming Patients</h2>
        <div class="unicare-card-dark doctor-patients-panel doctor-patients-panel--dark">
            @if ($upcomingPatients->isEmpty())
                <p class="text-sm opacity-75 py-4">No upcoming patients.</p>
            @else
                <table class="unicare-table doctor-patients-table doctor-patients-table--dark">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($upcomingPatients as $index => $row)
                            <tr class="animate-unicare-in stagger-{{ min($index + 1, 8) }}">
                                <td>{{ $row->patient_name }}</td>
                                <td>{{ $row->type_label }}</td>
                                <td>{{ $row->start_time }}</td>
                                <td>{{ $row->end_time }}</td>
                                <td>{{ $row->reason ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
            <div class="pup-watermark"></div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════
         ENCOUNTER MODAL
    ══════════════════════════════════════════ --}}
    <div
        x-show="isOpen"
        x-cloak
        x-transition:enter="backdrop-enter"
        x-transition:enter-start="backdrop-enter-start"
        x-transition:enter-end="backdrop-enter-end"
        x-transition:leave="backdrop-leave"
        x-transition:leave-start="backdrop-leave-start"
        x-transition:leave-end="backdrop-leave-end"
        class="doctor-encounter-modal"
        @keydown.escape.window="close()"
    >
        <div class="doctor-encounter-modal__backdrop" @click="close()"></div>

        <div class="doctor-encounter-modal__panel glass-panel glass-panel--padded" @click.stop>
            <div class="flex items-center justify-between mb-6">
                <h3 class="section-title mb-0">Edit Encounter</h3>
                <button type="button" class="drawer-close" style="position:static" @click="close()">×</button>
            </div>

            <form :action="formAction" method="POST">
                @csrf

                {{-- ── Chief Complaint & Diagnosis ── --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="form-label" for="chief_complaint">Chief Complaint</label>
                        <input type="text" id="chief_complaint" name="chief_complaint"
                            class="form-input w-full" x-model="chief_complaint">
                    </div>
                    <div>
                        <label class="form-label" for="diagnosis">Diagnosis</label>
                        <input type="text" id="diagnosis" name="diagnosis"
                            class="form-input w-full" x-model="diagnosis">
                    </div>
                </div>

                {{-- ── Notes ── --}}
                <div class="mb-4">
                    <label class="form-label" for="notes">Clinical Notes</label>
                    <textarea id="notes" name="notes" class="form-textarea w-full"
                        rows="3" x-model="notes"></textarea>
                </div>

                {{-- ── Vitals ── --}}
                <div class="mb-6">
                    <p class="form-label mb-2">Vitals</p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div>
                            <label class="text-xs opacity-60 mb-1 block">Blood Pressure</label>
                            <input type="text" name="vitals[bp]" class="form-input w-full"
                                placeholder="120/80" x-model="vitals.bp">
                        </div>
                        <div>
                            <label class="text-xs opacity-60 mb-1 block">Heart Rate (bpm)</label>
                            <input type="number" name="vitals[hr]" class="form-input w-full"
                                placeholder="72" x-model="vitals.hr">
                        </div>
                        <div>
                            <label class="text-xs opacity-60 mb-1 block">Temp (°C)</label>
                            <input type="number" name="vitals[temp_c]" class="form-input w-full"
                                placeholder="36.6" step="0.1" x-model="vitals.temp_c">
                        </div>
                        <div>
                            <label class="text-xs opacity-60 mb-1 block">Weight (kg)</label>
                            <input type="number" name="vitals[weight_kg]" class="form-input w-full"
                                placeholder="70" step="0.1" x-model="vitals.weight_kg">
                        </div>
                    </div>
                </div>

                {{-- ── Prescriptions ── --}}
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-3">
                        <p class="form-label mb-0">Prescriptions</p>
                        <button type="button"
                            class="text-xs underline opacity-60 hover:opacity-100"
                            @click="addRx()">+ Add drug</button>
                    </div>

                    <template x-for="(rx, i) in prescriptions" :key="i">
                        <div class="encounter-rx-row">
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-2">
                                <div>
                                    <label class="text-xs opacity-60 mb-1 block">Drug Name</label>
                                    <input type="text" :name="`prescriptions[${i}][drug_name]`"
                                        class="form-input w-full" placeholder="e.g. Amoxicillin"
                                        x-model="rx.drug_name">
                                </div>
                                <div>
                                    <label class="text-xs opacity-60 mb-1 block">Dosage</label>
                                    <input type="text" :name="`prescriptions[${i}][dosage]`"
                                        class="form-input w-full" placeholder="e.g. 500mg"
                                        x-model="rx.dosage">
                                </div>
                                <div>
                                    <label class="text-xs opacity-60 mb-1 block">Frequency</label>
                                    <input type="text" :name="`prescriptions[${i}][frequency]`"
                                        class="form-input w-full" placeholder="e.g. 3x daily"
                                        x-model="rx.frequency">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                <div>
                                    <label class="text-xs opacity-60 mb-1 block">Duration</label>
                                    <input type="text" :name="`prescriptions[${i}][duration]`"
                                        class="form-input w-full" placeholder="e.g. 7 days"
                                        x-model="rx.duration">
                                </div>
                                <div>
                                    <label class="text-xs opacity-60 mb-1 block">Valid Until</label>
                                    <input type="date" :name="`prescriptions[${i}][valid_until]`"
                                        class="form-input w-full" x-model="rx.valid_until">
                                </div>
                                <div>
                                    <label class="text-xs opacity-60 mb-1 block">Instructions</label>
                                    <input type="text" :name="`prescriptions[${i}][instructions]`"
                                        class="form-input w-full" placeholder="e.g. Take with food"
                                        x-model="rx.instructions">
                                </div>
                            </div>
                            <button
                                type="button"
                                class="text-xs text-red-500 underline mt-2 hover:text-red-700"
                                x-show="prescriptions.length > 1"
                                @click="removeRx(i)"
                            >Remove</button>

                            <hr class="my-4 opacity-10"
                                x-show="prescriptions.length > 1 && i < prescriptions.length - 1">
                        </div>
                    </template>
                </div>

                {{-- ── Actions ── --}}
                <div class="flex gap-3">
                    <button type="submit" class="unicare-btn-primary">Save Encounter</button>
                    <button type="button" class="unicare-btn-danger" @click="close()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.doctor-encounter-modal {
    position: fixed;
    inset: 0;
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: center;
}
.doctor-encounter-modal__backdrop {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.35);
    backdrop-filter: blur(2px);
}
.doctor-encounter-modal__panel {
    position: relative;
    z-index: 51;
    width: 100%;
    max-width: 56rem;
    max-height: 90vh;
    overflow-y: auto;
}
.encounter-rx-row {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 0.75rem;
}
</style>
@endsection