---
title: Profanity
description:
    The Profanity plugin will scan your codebase for profanity in places like comments, constants
    and properties
---

# Profanity

**Source code**:
[github.com/pestphp/pest-plugin-profanity](https://github.com/pestphp/pest-plugin-profanity)

The Profanity plugin will scan your codebase for profanity in places like comments, constants and
properties. This can help you maintain a more professional and respectful codebase. As developers,
we've all faced moments of frustration, whether it be debugging a persistent issue or trying to
decipher confusing code written by someone else. These moments can sometimes result in the inclusion
of profanity in your code.

To start using Pest's Profanity plugin, you need to require the plugin via Composer.

```bash
composer require pestphp/pest-plugin-profanity --dev
```

After requiring the plugin, you may utilize the `--profanity` option to generate a report of your
profanity.

```bash
./vendor/bin/pest --profanity
```

Profanity does not require you to write any tests. Instead, it analyses your codebase and generates
a report of your profanity. This report will display a list of files and their corresponding
profanity results.

<img src="/assets/img/profanity.png" style="width: 100%;" />

If any of your files contain profanity, they will be highlighted in red and displayed using their
respective line numbers and the profane word(s) that have been found.

As an example, `pr31(fuck)` means that the word "fuck" was found on line 31.

## Specific Language

Often, a codebase is a single language, so you would only want to flag profanity for the language of
the codebase. To do this, you can use the `--language` option:

```bash
./vendor/bin/pest --profanity --language=en
```

If needed, you can pass in multiple comma separated languages too:

```bash
./vendor/bin/pest --profanity --language=en,da
```

We currently support: `ar`, `da`, `en`, `es`, `it`, `ja`, `nl`, `pt_BR` and `ru`. If no language is
specified, we use `en` as the default.

## Include Words

There may be times when you want to flag certain words specific to your application as profane. To
do this, you can use the `--include` option:

```bash
./vendor/bin/pest --profanity --include=elephpant
```

## Exclude Words

There may be times when you want to exclude certain words from being flagged up as profane. To do
this, you can use the `--exclude` option:

```bash
./vendor/bin/pest --profanity --exclude=elephpant
```

## Compact Output

Often, when checking for profanity, you only want to see files that do contain profanity. To do
this, you can use the `--compact` option:

```bash
./vendor/bin/pest --profanity --compact
```

## Different Formats

In addition, Pest supports reporting the profanity results to a specific file:

```bash
./vendor/bin/pest --profanity --output=my-report.json
```

## Exclude Lines

If you wish to exclude certain lines that contain profanity from being flagged, without excluding
words from the whole application, you can tell the checker to ignore certain lines:

```php
const string Fuck; // @pest-ignore-profanity
```

---

In this chapter, we have discussed Pest's Profanity plugin and how it can be used to help you
maintain a professional codebase. In the following chapter, we will explore additional CLI options
that Pest provides: [CLI API Reference](/docs/cli-api-reference)
