<?php

declare(strict_types=1);

namespace Modules\Shared\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Password as BasePassword;

/**
 * Class Password
 *
 * Adaptive password validation rule that adjusts complexity based on the environment.
 */
class Password implements ValidationRule
{
    /**
     * The underlying Laravel password rule.
     */
    protected BasePassword $rule;

    /**
     * Create a new password rule instance.
     */
    public function __construct(BasePassword $rule)
    {
        $this->rule = $rule;
    }

    /**
     * Default rule with 8 characters minimum.
     */
    public static function low(): BasePassword
    {
        return BasePassword::min(8);
    }

    /**
     * Medium security: 8 chars + letters + numbers.
     */
    public static function medium(): BasePassword
    {
        return BasePassword::min(8)->letters()->numbers();
    }

    /**
     * High security: 12 chars + mixed case + numbers + symbols.
     */
    public static function high(): BasePassword
    {
        return BasePassword::min(12)->letters()->mixedCase()->numbers()->symbols()->uncompromised();
    }

    /**
     * Automatically select rule based on environment.
     */
    public static function auto(): BasePassword
    {
        if (app()->isProduction()) {
            return self::high();
        }

        return self::low();
    }

    /**
     * Run the validation rule.
     *
     * @param \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $this->rule->validate($attribute, $value, $fail);
    }
}
