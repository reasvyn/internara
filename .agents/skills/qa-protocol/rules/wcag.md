# Rules: WCAG 2.1 — Web Content Accessibility Guidelines

> Source: https://www.w3.org/TR/WCAG21/
> Version: 2.1 (Level AA target)
> Applicability: All web applications

## Overview

WCAG 2.1 provides guidelines for making web content accessible to people with disabilities.
Level AA is the standard target for most web applications. This document focuses on
code-level checks relevant to Blade/Livewire applications.

## 12 Guidelines (Quick Reference)

### 1. Perceivable

#### 1.1 Text Alternatives (WCAG 1.1.1)

**All non-text content must have a text alternative.**

```blade
{* BAD — missing alt *}
<img src="logo.png">

{* GOOD — descriptive alt *}
<img src="logo.png" alt="Company Name logo">

{* GOOD — decorative image *}
<img src="decoration.png" alt="" role="presentation">
```

**Checks:**
- [ ] All `<img>` tags have `alt` attribute
- [ ] Decorative images have `alt=""`
- [ ] Icons have accessible labels (`aria-label` or visually hidden text)
- [ ] Charts/graphs have text equivalents

#### 1.2 Time-Based Media (WCAG 1.2.1-1.2.5)

- [ ] Videos have captions (if applicable)
- [ ] Audio has transcripts (if applicable)

#### 1.3 Adaptable (WCAG 1.3.1-1.3.5)

**Content can be presented in different ways without losing meaning.**

```blade
{* BAD — using div for layout only *}
<div class="header">Title</div>
<div class="nav">
    <div class="nav-item">Home</div>
</div>

{* GOOD — semantic HTML *}
<header><h1>Title</h1></header>
<nav>
    <ul><li><a href="/">Home</a></li></ul>
</nav>
```

**Checks:**
- [ ] Semantic HTML used (header, nav, main, section, article, footer)
- [ ] Heading hierarchy is logical (h1 > h2 > h3, no skipping)
- [ ] Lists use `<ul>`, `<ol>`, `<dl>` (not divs)
- [ ] Tables have `<th>`, `<caption>`, `<thead>` for data tables
- [ ] Form fields have associated `<label>` elements
- [ ] `aria-label` or `aria-labelledby` used where visible label is insufficient

#### 1.4 Distinguishable (WCAG 1.4.1-1.4.13)

**Content must be easy to see and hear.**

**Color Contrast (WCAG 1.4.3):**
- Normal text: minimum 4.5:1 contrast ratio
- Large text (≥18pt or ≥14pt bold): minimum 3:1 contrast ratio
- Check: Use browser dev tools or axe DevTools to verify contrast

**Color Not Sole Indicator (WCAG 1.4.1):**
```blade
{* BAD — color only *}
<span class="text-red">Error</span>
<span class="text-green">Success</span>

{* GOOD — color + text *}
<span class="text-red">Error: <x-icon name="x-circle" /></span>
<span class="text-success">Success: <x-icon name="check-circle" /></span>
```

**Text Resize (WCAG 1.4.4):**
- [ ] Text can be resized to 200% without loss of content
- [ ] No horizontal scrolling at 200% zoom

**Responsive (WCAG 1.4.10):**
- [ ] Content reflows at 320px width (mobile)
- [ ] No content hidden at narrow widths

### 2. Operable

#### 2.1 Keyboard Accessible (WCAG 2.1.1)

**All functionality available via keyboard.**

**Checks:**
- [ ] All links focusable via Tab
- [ ] All form fields focusable via Tab
- [ ] All custom buttons/widgets focusable
- [ ] No keyboard traps (can Tab away from any element)
- [ ] Enter/Space activates buttons and links
- [ ] Escape closes modals/dropdowns

```blade
{* BAD — not keyboard accessible *}
<div onclick="doSomething()">Click me</div>

{* GOOD — keyboard accessible *}
<button type="button" wire:click="doSomething">Click me</button>
```

#### 2.2 Enough Time (WCAG 2.2.1)

- [ ] Session timeout gives adequate warning
- [ ] Auto-updating content can be paused
- [ ] No time limits on form completion (or adjustable)

#### 2.3 Seizures (WCAG 2.3.1)

- [ ] No content flashes more than 3 times per second
- [ ] Check: Animations, loading indicators

#### 2.4 Navigable (WCAG 2.4.1-2.4.7)

```blade
{* BAD — no skip link *}
<body>
    <nav>...20 links...</nav>

{* GOOD — skip link *}
<body>
    <a href="#main-content" class="sr-only focus:not-sr-only">
        Skip to main content
    </a>
    <nav>...20 links...</nav>
    <main id="main-content">...</main>
```

**Checks:**
- [ ] Skip navigation link present
- [ ] Page titles are descriptive and unique
- [ ] Focus order is logical (matches visual order)
- [ ] Focus indicator is visible (not `outline: none` without replacement)
- [ ] Link text is descriptive (not "click here", "read more")
- [ ] Headings and labels describe topic/purpose

### 3. Understandable

#### 3.1 Readable (WCAG 3.1.1-3.1.2)

- [ ] Page language declared (`<html lang="en">` or `lang="id"`)
- [ ] Language changes marked up for foreign phrases

#### 3.2 Predictable (WCAG 3.2.1-3.2.5)

- [ ] Focus doesn't trigger unexpected context changes
- [ ] Navigation is consistent across pages
- [ ] Form submissions don't cause unexpected changes

#### 3.3 Input Assistance (WCAG 3.3.1-3.3.4)

```blade
{* GOOD — error associated with field *}
<x-form.input
    label="Email"
    name="email"
    wire:model="email"
    :error="$errors->first('email')"
/>

{* GOOD — error summary at top *}
@if ($errors->any())
    <div role="alert" aria-live="polite">
        <h2>Please correct the following errors:</h2>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
```

**Checks:**
- [ ] Input errors are identified in text (not just color)
- [ ] Error messages describe what went wrong and how to fix it
- [ ] Labels/instructions provided for user input
- [ ] Important submissions are confirmed or reversible

### 4. Robust

#### 4.1 Compatible (WCAG 4.1.1-4.1.3)

**Checks:**
- [ ] HTML validates (no duplicate IDs, proper nesting)
- [ ] Custom components have appropriate ARIA roles
- [ ] Status messages use `aria-live` (for dynamic content updates)
- [ ] No duplicate `id` attributes on the same page

```blade
{* GOOD — live region for dynamic updates *}
<div wire:loading class="sr-only" aria-live="polite">
    Loading...
</div>

{* GOOD — status message for form submission *}
<div wire:after="submitForm" aria-live="polite" class="sr-only">
    Form submitted successfully.
</div>
```

## Livewire-Specific Accessibility

Livewire components present unique accessibility challenges:

### Dynamic Content Updates

```blade
{* After Livewire updates the DOM, screen readers may not notice *}
{* Use wire:loading and aria-live for status updates *}

<div wire:loading.delay aria-live="polite">
    Saving changes...
</div>

<div wire:loading.delay.remove>
    {{-- Content visible when NOT loading --}}
</div>
```

### Modal Dialogs

```blade
{* Focus must be trapped inside modal *}
{* Focus must return to trigger when modal closes *}

<div
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal-title"
    x-data="{ open: @entangle('showModal') }"
    x-show="open"
    x-on:keydown.escape.window="open = false"
>
    <h2 id="modal-title">Dialog Title</h2>
    ...
</div>
```

### Table Sorting/Pagination

```blade
{* Announce sort changes to screen readers *}
<div wire:loading.remove aria-live="polite">
    @foreach ($items as $item)
        ...
    @endforeach
</div>
```

## Testing Tools

| Tool | Purpose | Command |
|------|---------|---------|
| axe DevTools | Browser extension for accessibility audit | Install Chrome extension |
| Lighthouse | Google's accessibility audit | Chrome DevTools > Lighthouse |
| WAVE | Web accessibility evaluation | https://wave.webaim.org/ |
| pa11y | CLI accessibility testing | `npx pa11y https://example.com` |
| Colour Contrast Analyser | Desktop contrast checker | Free download from TPGi |

## Severity Scale

| Level | Meaning |
|-------|---------|
| **A** | Must satisfy — without it, some users cannot access content |
| **AA** | Should satisfy — addresses the most common barriers (TARGET) |
| **AAA** | May satisfy — enhances accessibility but not required for compliance |
