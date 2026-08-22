/**
 * MCP Authorize page — CSP-compliant external scripts
 * Previously inline in resources/views/mcp/authorize.blade.php
 */

// Theme appearance detection (reads data-appearance from html element)
(function () {
    const appearance = document.documentElement.dataset.appearance || 'system'
    if (appearance === 'system') {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
        if (prefersDark) {
            document.documentElement.classList.add('dark')
        }
    }
})()

// Form handling
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('authorizeForm')
    const button = document.getElementById('authorizeButton')
    const authorizeText = document.getElementById('authorizeText')
    const loadingSpinner = document.getElementById('loadingSpinner')

    if (form) {
        form.addEventListener('submit', function () {
            if (button) button.disabled = true
            if (authorizeText) authorizeText.textContent = 'Authorizing...'
            if (loadingSpinner) loadingSpinner.classList.remove('hidden')

            setTimeout(function () {
                const checkRedirect = setInterval(function () {
                    if (
                        !window.location.href.includes('/oauth/authorize') ||
                        window.location.search.includes('code=') ||
                        window.location.search.includes('error=')
                    ) {
                        clearInterval(checkRedirect)
                        window.close()
                    }
                }, 100)

                setTimeout(function () {
                    clearInterval(checkRedirect)
                    window.close()
                }, 5000)
            }, 200)
        })
    }

    const cancelForm = document.querySelector('form[method="POST"]:has(input[name="_method"][value="DELETE"])')
    if (cancelForm) {
        cancelForm.addEventListener('submit', function () {
            setTimeout(function () {
                window.close()
            }, 200)
        })
    }
})
