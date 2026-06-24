<div class="doctor-weekly-calendar glass-panel glass-panel--relative animate-unicare-scale-in stagger-2">
    <h2 class="doctor-weekly-calendar__title">Weekly Appointment</h2>

    <div class="doctor-weekly-calendar__header">
        <div class="doctor-weekly-calendar__header-spacer"></div>
        @foreach ($weekDays as $day)
            <div @class(['doctor-weekly-calendar__day', 'is-today' => $day['is_today']])>
                {{ $day['label'] }}
            </div>
        @endforeach
    </div>

    <div class="doctor-weekly-calendar__body">
        <div class="doctor-weekly-calendar__times">
            @foreach ($calendarHours as $hour)
                <div class="doctor-weekly-calendar__time">{{ $hour }}</div>
            @endforeach
        </div>

        <div class="doctor-weekly-calendar__grid">
            @foreach ($weekDays as $day)
                <div class="doctor-weekly-calendar__day-col">
                    @foreach ($calendarAppointments as $appointment)
                        @if ($appointment->grid_column === $day['column'])
                            <div
                                @class([
                                    'doctor-weekly-calendar__block',
                                    'doctor-weekly-calendar__block--finished' => $appointment->is_past,
                                    'doctor-weekly-calendar__block--upcoming' => !$appointment->is_past,
                                ])
                                style="top: {{ $appointment->top_percent }}%; height: {{ $appointment->height_percent }}%;"
                                @click="open({ ...@js($appointment), appointment_id: '{{ $appointment->id }}', appointment_status: '{{ $appointment->status }}' })"
                            >
                                <p class="doctor-weekly-calendar__block-name">{{ $appointment->patient_name }}</p>
                                <p class="doctor-weekly-calendar__block-type">{{ $appointment->type_label }}</p>
                                <p class="doctor-weekly-calendar__block-time">
                                    <span aria-hidden="true">&#128339;</span>
                                    {{ $appointment->time_label }}
                                </p>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>

    <div class="pup-watermark"></div>
</div>
