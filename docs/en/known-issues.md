# Known Issues

## Menu Config Undocumented
`config/menu.php` defines the sidebar navigation structure. It must be kept in sync with `routes/web.php` — adding a route without a corresponding menu entry means the page has no navigation link.

## Unused Package
`spatie/laravel-model-states` is installed but unused. State machine behavior is handled through enums and entity classes. Consider removing the dependency.

## Legacy Reference
The legacy modular monolith code is preserved in `legacy/internara-modular-monolith/` for reference only.
