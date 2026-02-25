# Browser Verification: Laravel Dusk Orchestration

This document formalizes the protocols for **Browser Automation** within the Internara project. 
While high-fidelity verification is essential, browser testing is secondary to **Livewire Verification** 
due to environmental overhead.

> **Strategic Recommendation:** Prioritize **Livewire Tests** and **Blade Rendering** assertions 
> for UI reactivity. Use **Laravel Dusk** only for critical end-to-end user flows (e.g., Auth, Setup).

---

## 1. Infrastructure: Laravel Dusk (via Pest)

Internara utilizes **Laravel Dusk** for browser automation, integrated into the **Pest v4** 
verification suite.

- **Objective**: Verify the "Happy Path" of cross-module user journeys in a real browser environment.
- **Engine**: Selenium/Chromedriver (default) orchestrated via the Dusk API.

### 1.1 Construction Pattern

Browser tests utilize the `$this->browse()` mechanism provided by the Dusk test case.

```php
test('it completes the administrative login flow', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/login')
                ->type('email', 'admin@internara.test')
                ->type('password', 'password')
                ->press('Login')
                ->assertPathIs('/dashboard')
                ->assertSee('Welcome');
    });
});
```

---

## 2. Verification Standards (ISO/IEC 29119)

### 2.1 Environmental Isolation

- **Browser Context**: Every test must run in a clean browser session to prevent state leakage.
- **Database**: Use the `DatabaseTransactions` or `RefreshDatabase` traits to maintain a clean 
  persistence baseline.

### 2.2 Aesthetic & Visual Verification

- **Screenshots**: In the event of a failure, Dusk automatically captures a screenshot in 
  `tests/Browser/screenshots/`.
- **Responsive Audit**: Utilize the `resize()` or `onMobile()` (custom wrapper) methods to verify 
  fulfillment of the **Mobile-First** mandate.

---

## 3. Execution & Triage

Browser tests are excluded from the default `composer test` pass to optimize verification speed.

```bash
# Run all tests including browser tests
php artisan dusk

# Run specific browser tests
php artisan dusk tests/Browser/ExampleTest.php
```

> **Note**: Ensure the **ChromeDriver** is updated and the local server is running (or Dusk is 
> configured to handle it) before execution.

---

_By strictly governing browser automation, Internara ensures that end-to-end flows remain 
traceable, stable, and verified against stakeholder requirements._
