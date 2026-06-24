import Alpine from 'alpinejs';

document.addEventListener('alpine:init', () => {
    Alpine.data('pageTransitions', () => ({
        leaving: false,
        navigate(event, url) {
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return;
            }
            event.preventDefault();
            this.leaving = true;
            setTimeout(() => {
                window.location.href = url;
            }, 200);
        },
    }));
    
    Alpine.data('recordDrawer', (records = []) => ({
        open: false,
        selected: null,
        records: records,

        show(id) {
            this.selected = this.records.find(r => r.id === id);
            this.open = true;
        },

        close() {
            this.open = false;
            setTimeout(() => { this.selected = null; }, 300);
        }
    }));

    Alpine.data('appointmentActions', (doctors = []) => ({
        doctors: doctors,
        search: '',
        showSchedule: false,
        showCancel: false,
        selectedDoctor: null,
        selectedDate: null,
        selectedSlot: null,
        reason: '',
        appointmentType: 'in_person',

        get filteredDoctors() {
            const q = this.search.toLowerCase();
            return this.doctors.filter(d =>
                d.name.toLowerCase().includes(q) ||
                d.specialty.toLowerCase().includes(q)
            );
        },

        get canSubmit() {
            return this.selectedDoctor && this.selectedDate && this.selectedSlot && this.reason.trim();
        },

        openSchedule() { this.showSchedule = true; },
        openCancel()   { this.showCancel   = true; },

        closePanels() {
            this.showSchedule = false;
            this.showCancel   = false;
        },

        selectDoctor(doctor) {
            this.selectedDoctor = doctor;
        },

        backToDoctors() {
            this.selectedDoctor = null;
            this.selectedDate   = null;
            this.selectedSlot   = null;
        },

        isSlotSelected(date, slot) {
            return this.selectedDate === date && this.selectedSlot === slot;
        },

        selectSlot(date, slot) {
            this.selectedDate = date;
            this.selectedSlot = slot;
        },

        submitBooking() {
            if (!this.canSubmit) return;

            fetch('/patient/appointments', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                        ?? document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1],
                },
                body: JSON.stringify({
                    doctor_id:        this.selectedDoctor.id,
                    appointment_date: this.selectedDate,
                    start_time:       this.selectedSlot,
                    type:             this.appointmentType,
                    reason:           this.reason,
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message ?? 'Booking failed. Please try again.');
                }
            })
            .catch(() => alert('Something went wrong.'));
        },
    }));

    Alpine.data('onboardingForm', (initialSlots, initialDuration) => ({
        duration: parseInt(initialDuration),
        
        // Standard week
        days: [
            { weekday: 1, label: 'Monday', short: 'Mon', enabled: false, selectedStarts: [] },
            { weekday: 2, label: 'Tuesday', short: 'Tue', enabled: false, selectedStarts: [] },
            { weekday: 3, label: 'Wednesday', short: 'Wed', enabled: false, selectedStarts: [] },
            { weekday: 4, label: 'Thursday', short: 'Thu', enabled: false, selectedStarts: [] },
            { weekday: 5, label: 'Friday', short: 'Fri', enabled: false, selectedStarts: [] },
            { weekday: 6, label: 'Saturday', short: 'Sat', enabled: false, selectedStarts: [] },
            { weekday: 0, label: 'Sunday', short: 'Sun', enabled: false, selectedStarts: [] }
        ],

        // Dynamically build the time blocks based on the selected duration!
        get visibleSlots() {
            let slots = [];
            let currentMin = 8 * 60; // 08:00
            let endMin = 17 * 60;    // 17:00

            while (currentMin + this.duration <= endMin) {
                let startH = Math.floor(currentMin / 60).toString().padStart(2, '0');
                let startM = (currentMin % 60).toString().padStart(2, '0');
                
                let nextMin = currentMin + this.duration;
                let endH = Math.floor(nextMin / 60).toString().padStart(2, '0');
                let endM = (nextMin % 60).toString().padStart(2, '0');

                let formatTime = (min) => {
                    let h = Math.floor(min / 60);
                    let m = (min % 60).toString().padStart(2, '0');
                    return `${h % 12 || 12}:${m} ${h >= 12 ? 'PM' : 'AM'}`;
                };

                slots.push({
                    start: `${startH}:${startM}`,
                    end: `${endH}:${endM}`,
                    label: `${formatTime(currentMin)} – ${formatTime(nextMin)}`
                });

                currentMin += this.duration;
            }
            return slots;
        },

        get durationHint() { return `Patients will book ${this.duration}-minute appointments.`; },

        setDuration(val) {
            this.duration = val;
            // If they change the duration, clear the grid so times don't misalign
            this.days.forEach(d => d.selectedStarts = []);
        },

        toggleDay(weekday) {
            let day = this.days.find(d => d.weekday === weekday);
            if (day) day.enabled = !day.enabled;
        },

        selectAll(weekday) {
            let day = this.days.find(d => d.weekday === weekday);
            if (day) day.selectedStarts = this.visibleSlots.map(s => s.start);
        },

        clearAll(weekday) {
            let day = this.days.find(d => d.weekday === weekday);
            if (day) day.selectedStarts = [];
        },

        toggleSlot(weekday, startStr) {
            let day = this.days.find(d => d.weekday === weekday);
            if (day) {
                let idx = day.selectedStarts.indexOf(startStr);
                if (idx > -1) day.selectedStarts.splice(idx, 1);
                else day.selectedStarts.push(startStr);
            }
        },

        isSlotSelected(weekday, startStr) {
            let day = this.days.find(d => d.weekday === weekday);
            return day ? day.selectedStarts.includes(startStr) : false;
        },

        selectedSlotCount(weekday) {
            let day = this.days.find(d => d.weekday === weekday);
            return day ? day.selectedStarts.length : 0;
        },

        selectedHours(weekday) {
            return (this.selectedSlotCount(weekday) * (this.duration / 60)).toFixed(1);
        },

        // Format exactly how Laravel Validation expects it
        prepareSubmit() {
            const container = document.getElementById('schedule-hidden-inputs');
            container.innerHTML = ''; 

            let idx = 0; 

            this.days.forEach(day => {
                if (day.enabled && day.selectedStarts.length > 0) {
                    
                    let inputWeekday = document.createElement('input');
                    inputWeekday.type = 'hidden';
                    inputWeekday.name = `schedules[${idx}][weekday]`;
                    inputWeekday.value = day.weekday;
                    container.appendChild(inputWeekday);

                    let rIdx = 0;
                    this.visibleSlots.forEach(slot => {
                        if (day.selectedStarts.includes(slot.start)) {
                            let inStart = document.createElement('input');
                            inStart.type = 'hidden';
                            inStart.name = `schedules[${idx}][ranges][${rIdx}][start]`;
                            inStart.value = slot.start;
                            container.appendChild(inStart);

                            let inEnd = document.createElement('input');
                            inEnd.type = 'hidden';
                            inEnd.name = `schedules[${idx}][ranges][${rIdx}][end]`;
                            inEnd.value = slot.end;
                            container.appendChild(inEnd);

                            rIdx++;
                        }
                    });
                    idx++;
                }
            });
        }
    }));



    Alpine.data('scheduleForm', (initialSchedules = []) => ({
        weekdays: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        schedules: initialSchedules.length
            ? initialSchedules.map((s) => ({
                weekday: String(s.weekday),
                ranges: s.ranges?.length ? s.ranges.map((r) => ({ start: r.start, end: r.end })) : [{ start: '08:00', end: '12:00' }],
            }))
            : [{ weekday: '1', ranges: [{ start: '08:00', end: '12:00' }] }],

        addDay() {
            this.schedules.push({ weekday: '1', ranges: [{ start: '08:00', end: '12:00' }] });
        },

        removeDay(dayIndex) {
            if (this.schedules.length > 1) {
                this.schedules.splice(dayIndex, 1);
            }
        },

        addRange(dayIndex) {
            this.schedules[dayIndex].ranges.push({ start: '13:00', end: '17:00' });
        },

        removeRange(dayIndex, rangeIndex) {
            if (this.schedules[dayIndex].ranges.length > 1) {
                this.schedules[dayIndex].ranges.splice(rangeIndex, 1);
            }
        },
    }));

    Alpine.data('encounterEdit', () => ({
        isOpen: false,
        uuid: '',
        chief_complaint: '',
        notes: '',
        drug_name: '',
        dosage: '',
        formAction: '',

        open(data) {
            this.uuid = data.uuid;
            this.chief_complaint = data.chief_complaint || '';
            this.notes = data.notes || '';
            this.drug_name = data.drug_name || '';
            this.dosage = data.dosage || '';
            this.formAction = `/doctor/appointments/${data.uuid}/encounter`;
            this.isOpen = true;
        },

        close() {
            this.isOpen = false;
        },
    }));
});

window.Alpine = Alpine;
Alpine.start();
