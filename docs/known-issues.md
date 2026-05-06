# Known Issues

## Incomplete Domains

The following domains have scaffold files (models, actions, controllers, policies, routes, tests) but lack full implementation:

| Domain | What exists | What is missing |
|---|---|---|
| Account | Actions, controller | Migration, views |
| Audit | Controller | Model, migration, views |
| Mentor | Action, controller | Model specialization (school_teacher / industry_supervisor), migration, views |
| Mentee | Controller | Views |
| Evaluation | Action | Model, migration, views |

## maryUI Component Compatibility

Scaffolded views use plain HTML instead of `x-mary-*` components due to `$this` context errors in maryUI. The workaround is functional but loses consistent UI styling. Migrate to maryUI components once the root cause is resolved.

## Private Disk Reference

Some document-related code references `Storage::disk('private')` but the filesystem config only defines `local`, `public`, and `s3` disks. The `local` disk points to `storage/app/private`, so `disk('local')` should be used instead.
