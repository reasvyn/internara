import { test } from 'node:test'
import assert from 'node:assert'
import fs from 'node:fs'
import { launch } from '../../Support/browser.js'

test('Browser smoke test: login page accessibility and title', async (t) => {
    const chromePath = process.env.CHROME_PATH || '/usr/bin/google-chrome'
    if (!fs.existsSync(chromePath)) {
        t.skip(`Chrome binary not found at ${chromePath} — skipping browser test`)
        return
    }

    const browser = await launch()
    try {
        const page = await browser.newPage()
        await page.goto(process.env.APP_URL || 'https://internara.web.id/login', {
            waitUntil: 'networkidle2',
            timeout: 15000,
        })
        const title = await page.title()
        assert.ok(title.length > 0)
    } finally {
        await browser.close()
    }
})
