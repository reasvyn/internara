import AOS from 'aos'

/**
 * Initialize AOS (Animate on Scroll).
 */
const initAOS = () => {
    AOS.init({
        duration: 800,
        easing: 'ease-out-cubic',
        once: true,
        offset: 20,
    })
}

// Initial Load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(initAOS, 150)
    })
} else {
    setTimeout(initAOS, 150)
}

// Livewire Integration
document.addEventListener('livewire:init', () => {
    initAOS()
})

document.addEventListener('livewire:navigated', () => {
    // Refresh and re-init to handle new DOM elements in SPA mode
    setTimeout(() => {
        AOS.refresh()
        initAOS()
    }, 50)
})
