import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

document.addEventListener('alpine:init', () => {
    // On Livewire pages `window.Alpine` is the Alpine instance Livewire boots.
    // On pages without Livewire, `window.Alpine` is set by us just before start.
    // Either way the handler receives the single active instance, so plugins and
    // global components are never registered twice and no second instance spawns.
    const target = window.Alpine ?? Alpine;

    target.plugin(collapse);

    target.data('bannerSlider', (count) => ({
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

// Livewire hosts its own Alpine instance (window.Alpine) and calls Alpine.start()
// itself on DOMContentLoaded. Booting a second Alpine here caused conflicting
// instances, broke Alpine.navigate and made admin navigation unreliable.
// Only boot our own Alpine on pages that have no Livewire at all.
if (window.Livewire === undefined) {
    window.Alpine = Alpine;
    Alpine.start();
}