<?php

namespace Nettixcode\Framework\Http;

use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as ValidatorFactory;
use Nettixcode\Framework\Exceptions\ValidationException;

class Request
{
    protected $illuminateRequest;
    protected static $validator;

    public function __construct()
    {
        $this->illuminateRequest = IlluminateRequest::capture();
        $this->initializeValidator();
    }

    protected function initializeValidator()
    {
        if (!self::$validator) {
            $loader = new ArrayLoader();
            $translator = new Translator($loader, 'en');
            self::$validator = new ValidatorFactory($translator);

            $this->registerCustomValidationRules(self::$validator);
        }
    }

    protected function registerCustomValidationRules($validator)
    {
        $this->addCustomRule($validator, 'lowercase', function ($attribute, $value, $parameters, $validator) {
            return $value === strtolower($value);
        }, 'The :attribute must be in lowercase.');

        $this->addCustomRule($validator, 'password_complex', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).+$/', $value);
        }, 'The :attribute must contain at least one uppercase letter, one number, and one special character.');

        $this->addCustomRule($validator, 'numeric_space', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^[\d\s.,]+$/', $value);
        }, 'The :attribute must contain only number and space.');

        $this->addCustomRule($validator, 'chars', function ($attribute, $value, $parameters, $validator) {
            if (strpos($parameters[0], 'chars:') === 0) {
                $pattern = $this->parseRegexRule($parameters[0]);
                return preg_match($pattern, $value);
            }
            return true;
        }, 'The :attribute format is invalid.');

        $this->addCustomRule($validator, 'phone', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^\+62[0-9]{9,13}$/', $value);
        }, 'The :attribute must be a valid phone number with country code +62.');
    }

    protected function addCustomRule($validator, $rule, $callback, $errorMessage)
    {
        $validator->extend($rule, $callback);
        $validator->replacer($rule, function ($message, $attribute, $rule, $parameters) use ($errorMessage) {
            return str_replace(':attribute', $attribute, $errorMessage);
        });
    }

    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->illuminateRequest, $method], $parameters);
    }

    public function validate($rules, $messages = [])
    {
        $validator = self::$validator->make($this->illuminateRequest->all(), $rules, $messages);

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            throw new ValidationException(json_encode($errors));
        }

        return true;
    }

    public function getIlluminateRequest()
    {
        return $this->illuminateRequest;
    }

    // Method to get request URI
    public function getUri()
    {
        return $this->illuminateRequest->getUri();
    }
}
