<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Route files are auto-included based on the module registry in
| config/module.php. For each registered module, if a route file
| exists at routes/web/{lowercase_module}.php, it is required here.
|
*/

$modules = config('module.list', []);
$routesDir = __DIR__.'/web';

foreach ($modules as $module) {
    $file = $routesDir.'/'.Str::lower($module).'.php';
    if (file_exists($file)) {
        require $file;
    }
}
