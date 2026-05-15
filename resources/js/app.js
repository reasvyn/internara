/**
 * UI Module Main Entry Point
 */

import flatpickr from 'flatpickr'
import 'flatpickr/dist/flatpickr.min.css'
window.flatpickr = flatpickr

/**
 * Echo / Broadcasting
 */
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
window.Pusher = Pusher

/**
 * Markdown Editor
 */
import { marked } from 'marked'
window.marked = marked

const appKey = import.meta.env.VITE_REVERB_APP_KEY

if (appKey) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: appKey,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        wsPath: import.meta.env.VITE_REVERB_PATH ?? '/',
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    })
}

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
