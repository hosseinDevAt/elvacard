import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

Alpine.plugin(collapse);

document.addEventListener('alpine:init', () => {
    Alpine.data('bannerSlider', (count) => ({
        count: count,
        slide: 0,
        timer: null,
        interval: 5000,

        init() {
            this.play();
        },

        go(index) {
            this.slide = index;
        },

        next() {
            this.slide = (this.slide + 1) % this.count;
        },

        prev() {
            this.slide = (this.slide - 1 + this.count) % this.count;
        },

        play() {
            if (this.timer || this.count <= 1) {
                return;
            }

            this.timer = setInterval(() => this.next(), this.interval);
        },

        pause() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        },

        destroy() {
            this.pause();
        },
    }));
});

window.Alpine = Alpine;

Alpine.start();