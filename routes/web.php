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

use App\Core\Support\ModuleManager;

foreach (ModuleManager::names() as $module) {
    $file = ModuleManager::routeFilePath($module);
    if (file_exists($file)) {
        require $file;
    }
}
