/**
 * UI Module Main Entry Point
 */

import Alpine from 'alpinejs'
import flatpickr from 'flatpickr'
import 'flatpickr/dist/flatpickr.min.css'
window.flatpickr = flatpickr

/**
 * Markdown Editor
 */
import { marked } from 'marked'
import DOMPurify from 'dompurify'
window.marked = marked
window.DOMPurify = DOMPurify

/**
 * Theme Initialization — TallstackUI convention.
 *
 * `<x-theme-switch>` persists mode to localStorage key `dark-theme`
 * ('true'/'false' legacy booleans or 'light'/'dark'/'system') and dispatches
 * a `theme` CustomEvent with {darkTheme, mode}. We apply BOTH `data-theme`
 * (semantic palette vars) and the `.dark` class (Tailwind/TallstackUI
 * `dark:` variant), plus mirror to the `theme` cookie for SSR accuracy.
 */
const resolveTheme = (mode) => {
    if (mode === 'system') {
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
    }
    return mode === 'dark' || mode === true ? 'dark' : mode === false ? 'light' : mode
}

const applyTheme = (mode) => {
    const resolved = resolveTheme(mode)
    document.documentElement.setAttribute('data-theme', resolved)
    document.documentElement.classList.toggle('dark', resolved === 'dark')
    document.cookie = `theme=${resolved};path=/;max-age=31536000;samesite=lax`
}

const storedTheme = () => {
    const t = localStorage.getItem('dark-theme')
    if (t === 'true') return 'dark'
    if (t === 'false') return 'light'
    return ['light', 'dark', 'system'].includes(t) ? t : 'system'
}

applyTheme(storedTheme())

document.addEventListener('theme', (event) => {
    applyTheme(event.detail?.mode ?? storedTheme())
})

/**
 * Alpine Helper Functions
 */
const getAlpineData = (element) => {
    if (!element) {
        return null
    }

    if (window.Alpine?.$data) {
        return window.Alpine.$data(element)
    }

    return element._x_dataStack?.[0] ?? null
}

const getChoicesState = (wrapper) => {
    const states = [...wrapper.querySelectorAll('[x-data]')]
        .map((element) => getAlpineData(element))
        .filter(Boolean)

    return {
        visual: states.find((state) => typeof state.focused !== 'undefined') ?? null,
        controller: states.find((state) => typeof state.clear === 'function') ?? null,
    }
}

const getChoicesTriggerContext = (event) => {
    const wrapper = event.target.closest('[data-ui-choices]')
    const trigger = event.target.closest('label.select')
    const optionsPanel = event.target.closest("[wire\\:key^='options-list-']")
    const interactiveIcon = event.target.closest('svg, button, a')

    if (!wrapper || !trigger || optionsPanel || interactiveIcon) {
        return null
    }

    return { wrapper }
}

/**
 * Choices UI Event Handlers
 */
const bindChoicesEvents = () => {
    if (window.__internaraChoicesToggleBound) {
        return
    }

    document.addEventListener(
        'pointerdown',
        (event) => {
            const context = getChoicesTriggerContext(event)
            if (!context) return

            const { visual } = getChoicesState(context.wrapper)
            context.wrapper.dataset.choicesWasOpen = String(Boolean(visual?.focused))
        },
        true,
    )

    document.addEventListener('click', (event) => {
        const context = getChoicesTriggerContext(event)
        if (!context) return

        const { wrapper } = context
        const wasOpen = wrapper.dataset.choicesWasOpen === 'true'
        delete wrapper.dataset.choicesWasOpen

        if (!wasOpen) return

        const { visual, controller } = getChoicesState(wrapper)
        if (!visual?.focused) return

        if (typeof controller?.clear === 'function') {
            controller.clear()
        } else {
            visual.focused = false
        }

        wrapper.querySelector('input')?.blur()
    })

    window.__internaraChoicesToggleBound = true
}

/**
 * Initialize Alpine Components and Events
 */
document.addEventListener('alpine:init', () => {
    bindChoicesEvents()
})

/**
 * Livewire & Theme Sync (CSP-compliant — previously inline in base.blade.php)
 */
document.addEventListener('livewire:navigated', () => {
    /**
     * FR-S2: reset focus to page heading after wire:navigate (WCAG).
     */
    const h1 = document.querySelector('h1[tabindex="-1"]') || document.querySelector('h1')
    if (h1) {
        h1.focus({ preventScroll: true })
    } else {
        document.getElementById('main-content')?.focus?.()
    }
})

document.addEventListener('livewire:init', () => {
    if (window.Livewire) {
        window.Livewire.on('language-changed', () => {
            window.location.reload()
        })
    }
})

/**
 * Alpine — TALL stack bootstrap.
 *
 * Previous absence (0.15.x) left every x-data/x-cloak/x-show inert,
 * permanently hiding x-cloak'd UI (theme switch, dropdowns, flyouts) via
 * `[x-cloak]{display:none!important}`. Must be started AFTER the
 * `alpine:init` listeners (ours + tallstackui's) are registered so their
 * Alpine.data() calls land before the DOM walk.
 */
window.Alpine = Alpine
Alpine.start()
