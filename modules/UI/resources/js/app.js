import AOS from 'aos';
import 'aos/dist/aos.css';

/**
 * Initialize AOS (Animate on Scroll).
 * We listen for both DOMContentLoaded and livewire:navigated to ensure
 * animations work during initial load and SPA transitions.
 */
const initAOS = () => {
    AOS.init({
        duration: 800,
        easing: 'ease-out-cubic',
        once: true,
        offset: 50,
    });
};

document.addEventListener('DOMContentLoaded', initAOS);
document.addEventListener('livewire:navigated', initAOS);
