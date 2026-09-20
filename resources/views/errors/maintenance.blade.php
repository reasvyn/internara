<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <title>{{ __('sysadmin.maintenance.title') }}</title>
</head>
<body>
    <h1>503</h1>
    <p>{{ __('sysadmin.maintenance.notice', ['reason' => $reason]) }}</p>
</body>
</html>
