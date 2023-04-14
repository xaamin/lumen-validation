<p align="center">
    <a href="https://github.com/xaamin/lumen-validation/actions"><img src="https://github.com/xaamin/lumen-validation/workflows/tests/badge.svg" alt="Build Status"></a>
    <a href="https://packagist.org/packages/xaamin/lumen-validation"><img src="https://img.shields.io/packagist/dt/xaamin/lumen-validation" alt="Total Downloads"></a>
    <a href="https://packagist.org/packages/xaamin/lumen-validation"><img src="https://img.shields.io/packagist/v/xaamin/lumen-validation" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/xaamin/lumen-validation"><img src="https://img.shields.io/packagist/l/xaamin/lumen-validation" alt="License"></a>
</p>

## Introduction

Lumen Validation provides request validation like Laravel does using Form Requests.

## Installation

This package requires requires php >= 8.0 and lumen >= 9

Step 1 - Install the package on your project
```
composer require xaamin/lumen-validation
```

Step 2 - Add the service provider in bootstrap/app.php
```
$app->register(
    Lumen\Validation\ValidationServiceProvider::class
);
```

Step 3 - Extend your request from `Lumen\Validation\BaseRequest` and injecting it into your controllers automatically will perform the validations. Use the `authorize` method to determine if the user could access the current request.

```
use Lumen\Validation\BaseRequest;

class CreateUserRequest extends BaseRequest
{
    protected function authorize()
    {
        return true;
    }

    protected function rules(): array
    {
        return  [
            'email' => ['required', 'string', 'unique:users'],
            'name' => ['required', 'string', 'max: 200'],
        ];
    }
}
```

## License

Lumen Validation is open-sourced software licensed under the [MIT license](LICENSE.md).
