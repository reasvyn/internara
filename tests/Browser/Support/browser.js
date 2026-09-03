import puppeteer from 'puppeteer-core'

/**
 * Launch a headless Chrome instance reusing the system binary.
 * @param {import('puppeteer-core').PuppeteerLaunchOptions} opts
 */
export async function launch(opts = {}) {
    return puppeteer.launch({
        executablePath: process.env.CHROME_PATH || '/usr/bin/google-chrome',
        headless: true,
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--ignore-certificate-errors',
            ...(opts.args ?? []),
        ],
        ignoreHTTPSErrors: true,
        ...opts,
    })
}
