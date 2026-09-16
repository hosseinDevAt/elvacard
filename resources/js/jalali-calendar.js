import * as jalaali from 'jalaali-js';

const PERSIAN_DIGITS = { 0: '۰', 1: '۱', 2: '۲', 3: '۳', 4: '۴', 5: '۵', 6: '۶', 7: '۷', 8: '۸', 9: '۹' };
const WEEKDAYS = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];
const MONTH_NAMES = ['', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];

function toPersianDigits(value) {
    return String(value).replace(/[0-9]/g, (d) => PERSIAN_DIGITS[d]);
}

function toAsciiDigits(value) {
    return String(value)
        .replace(/[۰-۹]/g, (d) => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d))
        .replace(/[٠-٩]/g, (d) => '٠١٢٣٤٥٦٧٨٩'.indexOf(d));
}

function pad2(n) {
    return String(n).padStart(2, '0');
}

export default function jalaliCalendar(config = {}) {
    return {
        mode: config.mode || 'date',
        min: config.min || '',
        max: config.max || '',
        open: false,
        ascii: '',
        display: '',
        selected: null,
        hours: 12,
        minutes: 0,
        viewYear: 0,
        viewMonth: 1,
        grid: [],
        weekDays: WEEKDAYS,
        monthNames: MONTH_NAMES,
        today: null,
        observer: null,

        toPd(value) {
            return toPersianDigits(value);
        },

        init() {
            const now = jalaali.toJalaali(new Date());
            this.today = { y: now.jy, m: now.jm, d: now.jd };

            this.resyncFromHidden();

            if (this.selected) {
                this.viewYear = this.selected.y;
                this.viewMonth = this.selected.m;
            } else {
                this.viewYear = this.today.y;
                this.viewMonth = this.today.m;
            }

            this.renderGrid();

            this.observer = new MutationObserver(() => this.resyncFromHidden());
            this.observer.observe(this.$refs.hidden, { attributes: true, attributeFilter: ['value'] });

            document.addEventListener('click', (event) => {
                if (!this.$el.contains(event.target)) {
                    this.open = false;
                }
            });
        },

        destroy() {
            if (this.observer) {
                this.observer.disconnect();
                this.observer = null;
            }
        },

        resyncFromHidden() {
            const value = this.$refs.hidden ? this.$refs.hidden.value : '';
            if (value === this.ascii) {
                return;
            }

            this.ascii = value;
            const parsed = this.parseAscii(value);
            this.selected = parsed ? { y: parsed.y, m: parsed.m, d: parsed.d } : null;
            this.display = this.formatDisplay(value);
        },

        parseAscii(value) {
            const ascii = toAsciiDigits(String(value || '').trim());
            const match = ascii.match(/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})(?:\s+(\d{1,2}):(\d{2}))?$/);
            if (!match) {
                return null;
            }

            const y = parseInt(match[1], 10);
            const m = parseInt(match[2], 10);
            const d = parseInt(match[3], 10);

            if (!jalaali.isValidJalaaliDate(y, m, d)) {
                return null;
            }

            return {
                y,
                m,
                d,
                h: match[4] ? parseInt(match[4], 10) : null,
                min: match[5] ? parseInt(match[5], 10) : null,
            };
        },

        formatAscii(date, time) {
            const base = `${date.y}/${pad2(date.m)}/${pad2(date.d)}`;
            if (!time || time.h === null || time.min === null) {
                return base;
            }

            return `${base} ${pad2(time.h)}:${pad2(time.min)}`;
        },

        formatDisplay(value) {
            if (!value) {
                return '';
            }

            const ascii = toAsciiDigits(String(value).trim());

            return ascii
                .replace(/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})(?:\s+(\d{1,2}):(\d{2}))?$/, (_, y, m, d, h, min) => {
                    let out = `${toPersianDigits(y)}/${toPersianDigits(m)}/${toPersianDigits(d)}`;
                    if (h !== undefined) {
                        out += ` ${toPersianDigits(h)}:${toPersianDigits(min)}`;
                    }

                    return out;
                })
                .replace(/\d/g, (d) => PERSIAN_DIGITS[d] || d);
        },

        handleManualInput(raw) {
            this.display = String(raw || '');
            const parsed = this.parseAscii(this.display);
            const time = this.mode === 'datetime' ? (this.hasTime(parsed) ? parsed : this.timeFromState()) : null;

            if (!parsed) {
                return;
            }

            this.selected = { y: parsed.y, m: parsed.m, d: parsed.d };
            this.push(this.formatAscii(parsed, time));
        },

        hasTime(parsed) {
            return parsed && parsed.h !== null && parsed.min !== null;
        },

        timeFromState() {
            return { h: this.hours, min: this.minutes };
        },

        prevMonth() {
            let y = this.viewYear;
            let m = this.viewMonth - 1;
            if (m < 1) {
                m = 12;
                y -= 1;
            }
            this.viewYear = y;
            this.viewMonth = m;
            this.renderGrid();
        },

        nextMonth() {
            let y = this.viewYear;
            let m = this.viewMonth + 1;
            if (m > 12) {
                m = 1;
                y += 1;
            }
            this.viewYear = y;
            this.viewMonth = m;
            this.renderGrid();
        },

        renderGrid() {
            const year = this.viewYear;
            const month = this.viewMonth;
            const daysInMonth = jalaali.jalaaliMonthLength(year, month);

            const firstGregorian = jalaali.jalaaliToDateObject(year, month, 1);
            const firstWeekday = firstGregorian.getDay();
            const leadingBlanks = (firstWeekday + 1) % 7;

            const cells = [];
            for (let i = 0; i < leadingBlanks; i++) {
                cells.push(null);
            }

            for (let d = 1; d <= daysInMonth; d++) {
                const selected = this.selected && this.selected.y === year && this.selected.m === month && this.selected.d === d;
                const isToday = this.today && this.today.y === year && this.today.m === month && this.today.d === d;
                const disabled = this.isDisabled(year, month, d);
                cells.push({ day: d, selected, isToday, disabled });
            }

            this.grid = cells;
        },

        isDisabled(y, m, d) {
            if (this.min) {
                const limit = this.parseAscii(this.min);
                if (limit && `${y}/${pad2(m)}/${pad2(d)}` < `${limit.y}/${pad2(limit.m)}/${pad2(limit.d)}`) {
                    return true;
                }
            }

            if (this.max) {
                const limit = this.parseAscii(this.max);
                if (limit && `${y}/${pad2(m)}/${pad2(d)}` > `${limit.y}/${pad2(limit.m)}/${pad2(limit.d)}`) {
                    return true;
                }
            }

            return false;
        },

        pickDay(y, m, d) {
            if (this.isDisabled(y, m, d)) {
                return;
            }

            this.selected = { y, m, d };

            const time = this.mode === 'datetime' ? this.timeFromState() : null;
            this.push(this.formatAscii(this.selected, time));

            if (this.mode === 'date') {
                this.open = false;
            }
        },

        setHours(h) {
            this.hours = Math.max(0, Math.min(23, parseInt(h, 10) || 0));
            this.refreshTime();
        },

        setMinutes(min) {
            this.minutes = Math.max(0, Math.min(59, parseInt(min, 10) || 0));
            this.refreshTime();
        },

        refreshTime() {
            if (!this.selected) {
                return;
            }

            this.push(this.formatAscii(this.selected, { h: this.hours, min: this.minutes }));
        },

        applyAndClose() {
            if (this.selected) {
                const time = this.mode === 'datetime' ? this.timeFromState() : null;
                this.push(this.formatAscii(this.selected, time));
            }
            this.open = false;
        },

        gotoToday() {
            this.viewYear = this.today.y;
            this.viewMonth = this.today.m;
            this.selected = { y: this.today.y, m: this.today.m, d: this.today.d };
            this.hours = this.mode === 'datetime' ? 12 : 0;
            this.minutes = 0;
            const time = this.mode === 'datetime' ? { h: this.hours, min: this.minutes } : null;
            this.push(this.formatAscii(this.selected, time));
            this.renderGrid();
            if (this.mode === 'date') {
                this.open = false;
            }
        },

        clearValue() {
            this.selected = null;
            this.push('');
            this.renderGrid();
            this.open = false;
        },

        push(value) {
            this.ascii = value;
            this.display = this.formatDisplay(value);

            const el = this.$refs.hidden;
            if (!el) {
                return;
            }

            el.value = value;
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.dispatchEvent(new Event('change', { bubbles: true }));
        },
    };
}