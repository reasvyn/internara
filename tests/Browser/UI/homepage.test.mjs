import { test } from 'node:test'
import assert from 'node:assert'
import fs from 'node:fs'
import { launch } from '../../Support/browser.js'

test('Browser: homepage layout, title normalization, and user-friendly registration card', async (t) => {
    const chromePath = process.env.CHROME_PATH || '/usr/bin/google-chrome'
    if (!fs.existsSync(chromePath)) {
        t.skip(`Chrome binary not found at ${chromePath} — skipping browser test`)
        return
    }

    const browser = await launch()
    try {
        const page = await browser.newPage()
        await page.setViewport({ width: 1280, height: 800 })

        const targetUrl = process.env.APP_URL || 'https://internara.web.id/'
        await page.goto(targetUrl, {
            waitUntil: 'domcontentloaded',
            timeout: 20000,
        })

        // 1. Verify Site Title normalization: "{Page Name} - {Brand Name}"
        const title = await page.title()
        assert.match(title, /Beranda\s*-\s*Internara/i, `Expected title to contain 'Beranda - Internara', got '${title}'`)

        // 2. Wait for main container and cards
        await page.waitForSelector('section', { timeout: 10000 })

        // 3. Verify Registration Card heading and Login Card heading exist
        const pageText = await page.evaluate(() => document.body.innerText)
        assert.ok(pageText.includes('Pendaftaran PKL'), "Expected page to include 'Pendaftaran PKL'")
        assert.ok(pageText.includes('Masuk ke Akun'), "Expected page to include 'Masuk ke Akun'")

        // 4. Verify humanized microcopy (no bureaucratic jargon)
        assert.ok(
            !pageText.includes('Periode pendaftaran belum diatur'),
            "Page should NOT contain bureaucratic copy 'Periode pendaftaran belum diatur'"
        )
        assert.ok(
            !pageText.includes('Jadwal pendaftaran PKL belum dikonfigurasi oleh pihak sekolah'),
            "Page should NOT contain bureaucratic copy 'Jadwal pendaftaran PKL belum dikonfigurasi oleh pihak sekolah'"
        )

        // 5. Verify either open registration or friendly unavailable status
        const hasOpenStatus = pageText.includes('Pendaftaran dibuka') || pageText.includes('Daftar Sekarang')
        const hasFriendlyUnavailable = pageText.includes('Tidak Ada Pendaftaran Terbuka') || pageText.includes('Pendaftaran ditutup')
        assert.ok(
            hasOpenStatus || hasFriendlyUnavailable,
            "Expected page to show either open registration or humanized unavailable/closed status"
        )

        // 6. Verify single primary CTA focal point and curvature hierarchy
        const cardEvaluation = await page.evaluate(() => {
            const cards = document.querySelectorAll('.grid > div')
            const registrationCard = cards[0]
            if (!registrationCard) return { found: false }

            // Check icon well does not have old conflicting primary gradient
            const iconWells = registrationCard.querySelectorAll('.from-primary\\/15')

            // Check buttons inside card
            const primaryButtons = registrationCard.querySelectorAll('button[class*="bg-primary"], a[class*="bg-primary"]')

            return {
                found: true,
                hasConflictingGradients: iconWells.length > 0,
                primaryButtonCount: primaryButtons.length,
                hasRedundantLoginButton: registrationCard.innerText.includes('Masuk Sekarang'),
            }
        })

        assert.ok(cardEvaluation.found, 'Registration card should exist in grid')
        assert.strictEqual(
            cardEvaluation.hasConflictingGradients,
            false,
            'Icon container should not have conflicting primary gradient background'
        )
        assert.strictEqual(
            cardEvaluation.hasRedundantLoginButton,
            false,
            'Registration card should not contain redundant login action button'
        )
    } finally {
        await browser.close()
    }
})
