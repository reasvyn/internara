/**
 * Login helper for headless tests.
 * @param {import('puppeteer-core').Page} page
 * @param {string} username
 * @param {string} password
 */
export async function login(page, username = 'superadmin', password = '@Password123') {
    await page.goto('https://internara.web.id/login', { waitUntil: 'networkidle2', timeout: 30000 })
    await page.waitForSelector('input[wire\\:model="form.identifier"]', { timeout: 10000 })
    await page.type('input[wire\\:model="form.identifier"]', username, { delay: 10 })
    await page.type('input[wire\\:model="form.password"]', password, { delay: 10 })
    await page.click('button[type="submit"]')
    await new Promise(r => setTimeout(r, 5000))
}
