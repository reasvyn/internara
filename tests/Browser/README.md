# Browser / Headless Tests

Headless browser tests using `puppeteer-core` + system Chrome, organized per module:
`tests/Browser/{Module}/*.test.mjs` (e.g., `tests/Browser/Auth/login.test.mjs`).

- `puppeteer-core` is installed as devDependency; it reuses `/usr/bin/google-chrome` (no browser download).
- Run: `npm run test:browser` (equivalent to `node --test tests/Browser/`).
- Shared helpers live in `tests/Support/` (not a test suite).

## Usage

```js
import { launch } from '../../Support/browser.js'
const browser = await launch()
const page = await browser.newPage()
await page.goto('https://internara.web.id/login')
```

## Helpers

- `tests/Support/browser.js` — `launch()` wrapper with sensible defaults (no-sandbox, ignore certs)
- `tests/Support/login.js` — `login(page, user, pass)` helper

## Structure

```
tests/Browser/
  Auth/          — login, password reset, logout journeys
  User/          — dashboard, profile journeys
  ...
  (one directory per module, mirroring tests/Arch, tests/Unit, tests/Feature)
```