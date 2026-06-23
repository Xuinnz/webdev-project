@php
    $weekdays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
@endphp

<div class="schedule-builder">
    <template x-for="(schedule, dayIndex) in schedules" :key="dayIndex">
        <div class="glass-panel glass-panel--padded mb-4 animate-unicare-in">
            <div class="flex flex-wrap items-end gap-4 mb-4">
                <div class="flex-1 min-w-[10rem]">
                    <label class="form-label" :for="'weekday-' + dayIndex">Weekday</label>
                    <select
                        class="form-select w-full"
                        :id="'weekday-' + dayIndex"
                        :name="'schedules[' + dayIndex + '][weekday]'"
                        x-model="schedule.weekday"
                        required
                    >
                        @foreach ($weekdays as $index => $label)
                            <option value="{{ $index }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button
                    type="button"
                    class="unicare-btn-danger"
                    @click="removeDay(dayIndex)"
                    x-show="schedules.length > 1"
                >
                    Remove day
                </button>
            </div>

            <template x-for="(range, rangeIndex) in schedule.ranges" :key="rangeIndex">
                <div class="flex flex-wrap items-end gap-4 mb-3">
                    <div>
                        <label class="form-label" :for="'start-' + dayIndex + '-' + rangeIndex">Start</label>
                        <input
                            type="time"
                            class="form-input"
                            :id="'start-' + dayIndex + '-' + rangeIndex"
                            :name="'schedules[' + dayIndex + '][ranges][' + rangeIndex + '][start]'"
                            x-model="range.start"
                            required
                        >
                    </div>
                    <div>
                        <label class="form-label" :for="'end-' + dayIndex + '-' + rangeIndex">End</label>
                        <input
                            type="time"
                            class="form-input"
                            :id="'end-' + dayIndex + '-' + rangeIndex"
                            :name="'schedules[' + dayIndex + '][ranges][' + rangeIndex + '][end]'"
                            x-model="range.end"
                            required
                        >
                    </div>
                    <button
                        type="button"
                        class="unicare-btn-danger"
                        @click="removeRange(dayIndex, rangeIndex)"
                        x-show="schedule.ranges.length > 1"
                    >
                        Remove range
                    </button>
                </div>
            </template>

            <button type="button" class="unicare-btn-primary mt-2" @click="addRange(dayIndex)">
                Add time range
            </button>
        </div>
    </template>

    <button type="button" class="unicare-btn-primary" @click="addDay()">
        Add day
    </button>
</div>
