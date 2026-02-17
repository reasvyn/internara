import AOS from 'aos'

/**
 * Initialize AOS (Animate on Scroll).
 */
const initAOS = () => {
    AOS.init({
        duration: 800,
        easing: 'ease-out-quad',
        once: true,
        offset: 60,
        anchorPlacement: 'top-bottom',
    })
}

// Initial Load - Delay until after preloader logic usually fires
window.addEventListener('load', () => {
    setTimeout(initAOS, 200)
})

// Livewire Integration
document.addEventListener('livewire:init', () => {
    initAOS()
})

document.addEventListener('livewire:navigated', () => {
    // Force scroll to top on navigation
    window.scrollTo({ top: 0, behavior: 'instant' })

    // Refresh and re-init to handle new DOM elements in SPA mode
    setTimeout(() => {
        AOS.refresh()
        initAOS()
    }, 100)
})
