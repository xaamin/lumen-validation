<?php

namespace Lumen\Validation;

use Closure as BaseClosure;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Lumen\Validation\Exceptions\AuthorizationException;

abstract class BaseRequest extends Request
{
    /**
     * App container
     *
     * @var \Illuminate\Contracts\Container\Container;
     */
    protected $app;

    /**
     * Validator
     *
     * @var \Illuminate\Contracts\Validation\Validator
     */
    protected $validator;

    /**
     * The response builder callback.
     *
     * @var \Closure
     */
    protected static $responseBuilder;

    /**
     *
     * The error formatter callback.
     *
     * @var \Closure
     */
    protected static $errorFormatter;

    /**
     *
     * The unathorized error formatter callback.
     *
     * @var \Closure
     */
    protected static $authorizationFailedBuilder;

    /**
     * Rules
     *
     * @return array<string, string|string[]>
     */
    abstract protected function rules();

    /**
     * Custom messages
     *
     * @return array<string, string>
     */
    protected function messages()
    {
        return [];
    }

    /**
     * Custom attributes
     *
     * @return array<string, string>
     */
    protected function attributes()
    {
        return [];
    }

    /**
     * Authorize the request
     *
     * @return bool
     */
    protected function authorize()
    {
        return true;
    }

    /**
     * Set the response builder callback.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public static function buildResponseUsing(BaseClosure $callback)
    {
        static::$responseBuilder = $callback;
    }

    /**
     * Set the error formatter callback.
     *
     * @param  \Closure  $callback
     *
     * @return void
     */
    public static function formatErrorsUsing(BaseClosure $callback)
    {
        static::$errorFormatter = $callback;
    }

    /**
     * Set the error formatter callback.
     *
     * @param  \Closure  $callback
     *
     * @return void
     */
    public static function handleFailedAuthorizationUsing(BaseClosure $callback)
    {
        static::$authorizationFailedBuilder = $callback;
    }

    /**
     * Handle the failed authorization
     *
     * @throws \Exception
     *
     * @return void
     */
    protected function handleFailedAuthorization()
    {
        if (static::$authorizationFailedBuilder !== null) {
             (static::$authorizationFailedBuilder)($this);

             return;
        }

        throw new AuthorizationException('This action is unathorized.');
    }

    /**
     * Validate the given request with the given rules.
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Lumen\Validation\Exceptions\AuthorizationException
     *
     * @return void
     */
    public function validate()
    {
        if (!$this->authorize()) {
            $this->handleFailedAuthorization();
        }

        $rules = $this->rules();

        $validator = $this->getValidationFactory()->make(
            $this->all(),
            $rules,
            $this->messages(),
            $this->attributes()
        );

        if ($validator->fails()) {
            $this->throwValidationException($validator);
        }
    }

    /**
     * Get the request input based on the given validation rules.
     *
     * @return array<string|float|int|bool|null>
     */
    public function validated()
    {
        return $this->only(collect($this->rules())->keys()->map(function ($rule) {
            return Str::contains($rule, '.') ? explode('.', $rule)[0] : $rule;
        })->unique()->toArray());
    }

    /**
     * Throw the failed validation exception.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     *
     * @throws \Illuminate\Validation\ValidationException
     *
     * @return void
     */
    protected function throwValidationException(Validator $validator)
    {
        $response = $this->buildFailedValidationResponse(
            $this,
            $this->formatValidationErrors($validator)
        );

        throw new ValidationException($validator, $response);
    }

    /**
     * Build a response based on the given errors.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array<mixed>  $errors
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function buildFailedValidationResponse(Request $request, array $errors)
    {
        if (static::$responseBuilder !== null) {
            return (static::$responseBuilder)($request, $errors);
        }

        return new JsonResponse([
            'code' => 'validation_error',
            'message' => 'The given data was invalid.',
            'errors' => $errors,
        ], 422);
    }

    /**
     * Format validation errors.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     *
     * @return array<mixed>
     */
    protected function formatValidationErrors(Validator $validator)
    {
        if (static::$errorFormatter !== null) {
            return (static::$errorFormatter)($validator);
        }

        return $validator->errors()->getMessages();
    }

    /**
     * Get a validation factory instance.
     *
     * @return \Illuminate\Contracts\Validation\Factory
     */
    protected function getValidationFactory()
    {
        /** @var \Illuminate\Contracts\Validation\Factory */
        return $this->app->make('validator');
    }

    /**
     * Set app container
     *
     * @param \Illuminate\Contracts\Container\Container $app
     *
     * @return BaseRequest
     */
    public function withContainer(Container $app)
    {
        $this->app = $app;

        return $this;
    }

    /**
     * Get the route handling the request.
     *
     * @param  string|null  $param
     * @param  mixed  $default
     *
     * @return array|string
     */
    public function route($param = null, $default = null)
    {
        $route = call_user_func($this->getRouteResolver());

        if (is_null($route) || is_null($param)) {
            return $route;
        }

        return $route[2][$param] ?? $default;
    }
}
