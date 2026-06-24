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
