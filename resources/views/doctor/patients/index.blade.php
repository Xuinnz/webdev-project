@extends('doctor.common.main')

@section('title', 'Patients')

@section('content')
    <div x-data="encounterEdit">
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
                                <th>Chief Complaint</th>
                                <th>Note</th>
                                <th>Prescription</th>
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
                                    <td>{{ $row->notes ?? '—' }}</td>
                                    <td>{{ $row->prescription ?? '—' }}</td>
                                    <td>
                                        <button
                                            type="button"
                                            class="doctor-edit-btn"
                                            title="Edit encounter"
                                            @click="open({
                                                uuid: @js($row->uuid),
                                                chief_complaint: @js($row->chief_complaint ?? ''),
                                                notes: @js($row->notes ?? ''),
                                                drug_name: @js($row->drug_name ?? ''),
                                                dosage: @js($row->dosage ?? ''),
                                            })"
                                        >
                                            &#9998;
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </section>

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
                                <th>Chief Complaint</th>
                                <th>Note</th>
                                <th>Prescription</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($upcomingPatients as $index => $row)
                                <tr class="animate-unicare-in stagger-{{ min($index + 1, 8) }}">
                                    <td>{{ $row->patient_name }}</td>
                                    <td>{{ $row->type_label }}</td>
                                    <td>{{ $row->start_time }}</td>
                                    <td>{{ $row->end_time }}</td>
                                    <td>{{ $row->chief_complaint ?? '—' }}</td>
                                    <td>{{ $row->notes ?? '—' }}</td>
                                    <td>{{ $row->prescription ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
                <div class="pup-watermark"></div>
            </div>
        </section>

        <div
            x-show="isOpen"
            x-cloak
            class="doctor-encounter-modal"
            @keydown.escape.window="close()"
        >
            <div class="doctor-encounter-modal__backdrop" @click="close()"></div>
            <div class="doctor-encounter-modal__panel glass-panel glass-panel--padded" @click.stop>
                <h3 class="section-title">Edit Encounter</h3>
                <form :action="formAction" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label" for="chief_complaint">Chief Complaint</label>
                        <input type="text" id="chief_complaint" name="chief_complaint" class="form-input w-full" x-model="chief_complaint">
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="notes">Note</label>
                        <textarea id="notes" name="notes" class="form-textarea w-full" rows="2" x-model="notes"></textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="form-label" for="drug_name">Prescription</label>
                            <input type="text" id="drug_name" name="drug_name" class="form-input w-full" x-model="drug_name" placeholder="Drug name">
                        </div>
                        <div>
                            <label class="form-label" for="dosage">Dosage</label>
                            <input type="text" id="dosage" name="dosage" class="form-input w-full" x-model="dosage" placeholder="e.g. 500mg">
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <button type="submit" class="unicare-btn-primary">Save</button>
                        <button type="button" class="unicare-btn-danger" @click="close()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
