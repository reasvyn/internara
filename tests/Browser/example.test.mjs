import { launch } from './Support/browser.js'
import { login } from './Support/login.js'

const browser = await launch()
const page = await browser.newPage()
await login(page)
console.log('After login URL:', page.url())
await page.goto('https://internara.web.id/admin/dashboard', { waitUntil: 'networkidle2' })
console.log('Admin dashboard URL:', page.url())
await browser.close()
console.log('Example headless test passed')
