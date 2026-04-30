<?php

declare(strict_types=1);

return [
    'name' => 'Permission',

    /*
    |--------------------------------------------------------------------------
    | Permission Model Key Type
    |--------------------------------------------------------------------------
    |
    | This option defines the type of the 'model_id' (morph key) for
    | Spatie Permission tables. It should match the User ID type.
    | Supported: "uuid", "id"
    | Default: "uuid"
    |
    */
    'model_key_type' => env('PERMISSION_MODEL_KEY_TYPE', 'uuid'),
];
