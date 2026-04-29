<?php

declare(strict_types=1);

return [
    'name' => 'Core',

    /*
    |--------------------------------------------------------------------------
    | Academic Year Transition Month
    |--------------------------------------------------------------------------
    |
    | Defines the month (1-12) when the academic year transitions.
    | For example, if set to 7 (July), then:
    | - January to June: uses previous year/this year
    | - July to December: uses this year/next year
    |
    */
    'academic_year_transition_month' => 7,
];
