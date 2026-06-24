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

    Alpine.data('onboardingForm', (initialSlots, initialDuration, existingSchedule = {}) => ({
        duration: parseInt(initialDuration),
    
        days: [
            { weekday: 1, label: 'Monday',    short: 'Mon', enabled: false, selectedStarts: [] },
            { weekday: 2, label: 'Tuesday',   short: 'Tue', enabled: false, selectedStarts: [] },
            { weekday: 3, label: 'Wednesday', short: 'Wed', enabled: false, selectedStarts: [] },
            { weekday: 4, label: 'Thursday',  short: 'Thu', enabled: false, selectedStarts: [] },
            { weekday: 5, label: 'Friday',    short: 'Fri', enabled: false, selectedStarts: [] },
            { weekday: 6, label: 'Saturday',  short: 'Sat', enabled: false, selectedStarts: [] },
            { weekday: 0, label: 'Sunday',    short: 'Sun', enabled: false, selectedStarts: [] },
        ],
    
        // ── Lifecycle ──────────────────────────────────────────────
        // Pre-populate days from existing schedule on profile edit
        // existingSchedule shape: { "1": ["08:00","08:30"], "3": ["09:00"] }
        // On onboarding it's always {}, so this is a no-op there
        init() {
            Object.entries(existingSchedule).forEach(([weekday, starts]) => {
                const day = this.days.find(d => d.weekday === parseInt(weekday));
                if (day && starts.length > 0) {
                    day.enabled        = true;
                    day.selectedStarts = starts;
                }
            });
        },
    
        // ── Slot generation ────────────────────────────────────────
        get visibleSlots() {
            const slots      = [];
            let currentMin   = 8 * 60;   // 08:00
            const endMin     = 17 * 60;  // 17:00
    
            const fmt = (min) => {
                const h = Math.floor(min / 60);
                const m = (min % 60).toString().padStart(2, '0');
                return `${h % 12 || 12}:${m} ${h >= 12 ? 'PM' : 'AM'}`;
            };
    
            while (currentMin + this.duration <= endMin) {
                const nextMin = currentMin + this.duration;
                const startH  = Math.floor(currentMin / 60).toString().padStart(2, '0');
                const startM  = (currentMin % 60).toString().padStart(2, '0');
                const endH    = Math.floor(nextMin / 60).toString().padStart(2, '0');
                const endM    = (nextMin % 60).toString().padStart(2, '0');
    
                slots.push({
                    start: `${startH}:${startM}`,
                    end:   `${endH}:${endM}`,
                    label: `${fmt(currentMin)} – ${fmt(nextMin)}`,
                });
    
                currentMin += this.duration;
            }
            return slots;
        },
    
        get durationHint() {
            return `Patients will book ${this.duration}-minute appointments.`;
        },
    
        // ── Duration ───────────────────────────────────────────────
        setDuration(val) {
            this.duration = val;
            // Clear all selected slots — time boundaries no longer align
            this.days.forEach(d => d.selectedStarts = []);
        },
    
        // ── Day controls ───────────────────────────────────────────
        toggleDay(weekday) {
            const day = this.days.find(d => d.weekday === weekday);
            if (day) day.enabled = !day.enabled;
        },
    
        // ── Slot controls ──────────────────────────────────────────
        toggleSlot(weekday, startStr) {
            const day = this.days.find(d => d.weekday === weekday);
            if (!day) return;
            const idx = day.selectedStarts.indexOf(startStr);
            if (idx > -1) day.selectedStarts.splice(idx, 1);
            else day.selectedStarts.push(startStr);
        },
    
        isSlotSelected(weekday, startStr) {
            const day = this.days.find(d => d.weekday === weekday);
            return day ? day.selectedStarts.includes(startStr) : false;
        },
    
        selectAll(weekday) {
            const day = this.days.find(d => d.weekday === weekday);
            if (day) day.selectedStarts = this.visibleSlots.map(s => s.start);
        },
    
        clearAll(weekday) {
            const day = this.days.find(d => d.weekday === weekday);
            if (day) day.selectedStarts = [];
        },
    
        // ── Display helpers ────────────────────────────────────────
        selectedSlotCount(weekday) {
            const day = this.days.find(d => d.weekday === weekday);
            return day ? day.selectedStarts.length : 0;
        },
    
        selectedHours(weekday) {
            return (this.selectedSlotCount(weekday) * (this.duration / 60)).toFixed(1);
        },
    
        // ── Submit ─────────────────────────────────────────────────
        // Injects hidden inputs in the shape Laravel validation expects:
        // schedules[0][weekday], schedules[0][ranges][0][start], etc.
        prepareSubmit() {
            const container = document.getElementById('schedule-hidden-inputs');
            container.innerHTML = '';
    
            let idx = 0;
            this.days.forEach(day => {
                if (!day.enabled || day.selectedStarts.length === 0) return;
    
                const wdInput   = document.createElement('input');
                wdInput.type    = 'hidden';
                wdInput.name    = `schedules[${idx}][weekday]`;
                wdInput.value   = day.weekday;
                container.appendChild(wdInput);
    
                let rIdx = 0;
                this.visibleSlots.forEach(slot => {
                    if (!day.selectedStarts.includes(slot.start)) return;
    
                    const inStart  = document.createElement('input');
                    inStart.type   = 'hidden';
                    inStart.name   = `schedules[${idx}][ranges][${rIdx}][start]`;
                    inStart.value  = slot.start;
                    container.appendChild(inStart);
    
                    const inEnd    = document.createElement('input');
                    inEnd.type     = 'hidden';
                    inEnd.name     = `schedules[${idx}][ranges][${rIdx}][end]`;
                    inEnd.value    = slot.end;
                    container.appendChild(inEnd);
    
                    rIdx++;
                });
    
                idx++;
            });
        },
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
        formAction: '',
        chief_complaint: '',
        diagnosis: '',
        notes: '',
        vitals: { bp: '', hr: '', temp_c: '', weight_kg: '' },
        prescriptions: [
            { drug_name: '', dosage: '', frequency: '', duration: '', instructions: '', valid_until: '' }
        ],

        openFromEl(el) {
            this.formAction = `/doctor/appointments/${el.dataset.uuid}/encounter`;
            this.chief_complaint = el.dataset.complaint || '';
            this.diagnosis       = el.dataset.diagnosis  || '';
            this.notes           = el.dataset.notes       || '';

            const v = JSON.parse(el.dataset.vitals || '{}');
            this.vitals = {
                bp:        v.bp        ?? '',
                hr:        v.hr        ?? '',
                temp_c:    v.temp_c    ?? '',
                weight_kg: v.weight_kg ?? '',
            };

            const rxRaw = JSON.parse(el.dataset.prescriptions || '[]');
            this.prescriptions = rxRaw.length
                ? rxRaw.map(rx => ({
                    drug_name:    rx.drug_name    || '',
                    dosage:       rx.dosage       || '',
                    frequency:    rx.frequency    || '',
                    duration:     rx.duration     || '',
                    instructions: rx.instructions || '',
                    valid_until:  rx.valid_until  || '',
                }))
                : [{ drug_name: '', dosage: '', frequency: '', duration: '', instructions: '', valid_until: '' }];

            this.isOpen = true;
        },

        close() { this.isOpen = false; },

        addRx() {
            this.prescriptions.push({
                drug_name: '', dosage: '', frequency: '',
                duration: '', instructions: '', valid_until: ''
            });
        },

        removeRx(i) {
            if (this.prescriptions.length > 1) this.prescriptions.splice(i, 1);
        },
    }));

});

window.Alpine = Alpine;
Alpine.start();
