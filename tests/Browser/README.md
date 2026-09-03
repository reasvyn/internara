# Browser / Headless Tests

Headless browser tests using `puppeteer-core` + system Chrome.

- `puppeteer-core` is installed as devDependency; it reuses `/usr/bin/google-chrome` (no browser download).
- Run: `npm run test:browser` or `node tests/Browser/run.mjs`

## Usage

```js
import { launch } from '../Support/browser.js'
const browser = await launch()
const page = await browser.newPage()
await page.goto('https://internara.web.id/login')
```

## Helpers

- `tests/Browser/Support/browser.js` — `launch()` wrapper with sensible defaults (no-sandbox, ignore certs)
- `tests/Browser/Support/login.js` — `login(page, user, pass)` helper

See `tests/Browser/example.test.mjs` for a minimal example.
