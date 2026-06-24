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
