@extends('patient.common.patientCommon')

@section('title', 'Medical Records')

@section('content')
    <div x-data="recordDrawer(@js($medical_records))" class="record-page">
        <h1 class="page-title animate-unicare-in stagger-1">Medical Records</h1>

        <div class="glass-panel glass-panel--padded animate-unicare-scale-in stagger-2">
            <table class="unicare-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Chief Complaint</th>
                        <th>Diagnosis</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($medical_records as $index => $medical_record)
                        <tr
                            class="is-clickable animate-unicare-in stagger-{{ min($index + 1, 8) }}"
                            @click="show({{ $medical_record['id'] }})"
                        >
                            <td>{{ $medical_record['record_date'] }}</td>
                            <td>{{ $medical_record['chief_complaint'] }}</td>
                            <td>{{ $medical_record['diagnosis'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div
            x-show="open"
            x-cloak
            x-transition:enter="backdrop-enter"
            x-transition:enter-start="backdrop-enter-start"
            x-transition:enter-end="backdrop-enter-end"
            x-transition:leave="backdrop-leave"
            x-transition:leave-start="backdrop-leave-start"
            x-transition:leave-end="backdrop-leave-end"
            class="drawer-backdrop"
            @click="close()"
        ></div>

        <aside
            x-show="open"
            x-cloak
            x-transition:enter="drawer-enter"
            x-transition:enter-start="drawer-enter-start"
            x-transition:enter-end="drawer-enter-end"
            x-transition:leave="drawer-leave"
            x-transition:leave-start="drawer-leave-start"
            x-transition:leave-end="drawer-leave-end"
            class="record-drawer"
        >
            <button type="button" class="drawer-close" @click="close()">×</button>

            <template x-if="selected">
                <div>
                    <p class="drawer-date animate-unicare-in" x-text="selected.record_date"></p>

                    <p class="drawer-doctor animate-unicare-in stagger-1">
                        Doctor: <span x-text="selected.doctor_name"></span>
                    </p>

                    <div class="drawer-details">
                        <p class="animate-unicare-in stagger-2">
                            <strong>Chief Complaint:</strong>
                            <span x-text="selected.chief_complaint"></span>
                        </p>
                        <p class="animate-unicare-in stagger-3">
                            <strong>Diagnosis:</strong>
                            <span x-text="selected.diagnosis"></span>
                        </p>
                        <p class="animate-unicare-in stagger-4">
                            <strong>Blood Pressure:</strong>
                            <span x-text="selected.vitals?.bp ?? '—'"></span>
                        </p>
                        <p class="animate-unicare-in stagger-5">
                            <strong>Heart Rate:</strong>
                            <span x-text="selected.vitals?.hr ? selected.vitals.hr + ' bpm' : '—'"></span>
                        </p>
                        <p class="animate-unicare-in stagger-6">
                            <strong>Temperature:</strong>
                            <span x-text="selected.vitals?.temp_c ? selected.vitals.temp_c + '°C' : '—'"></span>
                        </p>
                        <div class="animate-unicare-in stagger-7">
                            <strong>Prescriptions:</strong>
                            <template x-if="selected.prescriptions?.length">
                                <ul class="prescription-list">
                                    <template x-for="prescription in selected.prescriptions" :key="prescription.uuid">
                                        <li x-text="prescription.summary"></li>
                                    </template>
                                </ul>
                            </template>
                            <span x-show="!selected.prescriptions?.length">—</span>
                        </div>
                    </div>

                    <div class="pup-watermark"></div>
                </div>
            </template>
        </aside>
    </div>
@endsection
