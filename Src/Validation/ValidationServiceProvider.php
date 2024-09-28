<?php

namespace Nettixcode\Validation;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

class ValidationServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Aturan validasi custom
        Validator::extend('lowercase', function ($attribute, $value, $parameters, $validator) {
            return $value === strtolower($value);
        }, 'The :attribute must be in lowercase.');

        Validator::extend('password_complex', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).+$/', $value);
        }, 'The :attribute must contain at least one uppercase letter, one number, and one special character.');

        Validator::extend('phone', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^\+62[0-9]{9,13}$/', $value);
        }, 'The :attribute must be a valid phone number with country code +62.');

        Validator::extend('numeric_space', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^[\d\s.,]+$/', $value);
        }, 'The :attribute must contain only numbers and spaces.');

        Validator::extend('chars', function ($attribute, $value, $parameters, $validator) {
            if (strpos($parameters[0], 'chars:') === 0) {
                $pattern = $this->parseRegexRule($parameters[0]);
                return preg_match($pattern, $value);
            }
            return true;
        }, 'The :attribute format is invalid.');

        // Add more custom rules if needed
    }

    public function register()
    {
        //
    }

    private function parseRegexRule($rule)
    {
        $pattern = substr($rule, 6);
        if (preg_match('/^\[".*"\]$/', $pattern)) {
            $chars = json_decode($pattern, true);
            $escapedChars = array_map('preg_quote', $chars, array_fill(0, count($chars), '/'));

            return '/^[' . implode('', $escapedChars) . 'a-zA-Z]+$/'; // Tambahkan a-zA-Z untuk karakter alfabet
        }
        return $pattern;
    }
}
