<?php

namespace Lumen\Validation\Tests;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Lumen\Validation\BaseRequest;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Contracts\Validation\Validator;
use Lumen\Validation\LumenValidationServiceProvider;
use Lumen\Validation\Tests\Support\DummyBaseRequest;

class BaseRequestTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [LumenValidationServiceProvider::class];
    }

    /** @return void */
    public function testValidationPasses()
    {
        Route::post('/foo', function (DummyBaseRequest $request) {
            return 'bar';
        });

        $response = $this->post('/foo', [
            'name' => 'foo',
            'age' => 20
        ]);

        $response->assertStatus(200);
    }

    /** @return void */
    public function testUnauthorizedRequest()
    {
        Route::post('/foo', function (DummyBaseRequest $request) {
            return 'bar';
        });

        $response = $this->post('/foo', ['make_unauthorized' => true]);

        $response->assertStatus(403);
        $response->assertJson([
            'code' => 'access_denied'
        ]);
        $response->assertJsonStructure([
            'code', 'message'
        ]);
    }

    /** @return void */
    public function testValidationFails()
    {
        Route::post('/foo', function (DummyBaseRequest $request) {
            return 'bar';
        });

        $response = $this->post('/foo');

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'code',
            'message',
            'errors' => ['name', 'age']
        ]);
    }

    /** @return void */
    public function testCustomMessages()
    {
        Route::post('/foo', function (DummyBaseRequest $request) {
            return 'bar';
        });

        $response = $this->post('foo', [
            'name' => 'foo',
            'age' => 'age',
            'make_message' => true
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'code',
            'message',
            'errors' => ['age']
        ]);

        /** @phpstan-ignore-next-line */
        $data = $response->getData(true);

        $this->assertTrue(Str::of($data['errors']['age'][0])->is('The age* must be a number value'));
    }

    /** @return void */
    public function testCustomAttributes()
    {
        Route::post('/foo', function (DummyBaseRequest $request) {
            return 'bar';
        });

        $response = $this->post('foo', [
            'age' => 'age',
            'make_attribute' => true
        ]);

        /** @phpstan-ignore-next-line */
        $data = $response->getData(true);

        $this->assertTrue(Str::of($data['errors']['age'][0])->is('The oldness* must be a number.'));
    }

    /** @return void */
    public function testCustomBuildResponse()
    {
        BaseRequest::buildResponseUsing(function (Request $request, $errors) {
            return $errors;
        });

        Route::post('/foo', function (DummyBaseRequest $request) {
            return 'bar';
        });

        $response = $this->post('foo', [
            'age' => 'age',
            'make_attribute' => true
        ]);

        $response->assertJsonStructure([
            'name',
            'age'
        ]);
    }

    /** @return void */
    public function testCustomErrorFormatter()
    {
        BaseRequest::formatErrorsUsing(function (Validator $validator) {
            $errors = [];

            foreach ($validator->errors()->getMessages() as $key => $value) {
                foreach ($value as $message) {
                    $errors[] = [
                        'field' => $key,
                        'message' => $message
                    ];
                }
            }

            return [
                'errors' => $errors
            ];
        });

        Route::post('/foo', function (DummyBaseRequest $request) {
            return 'bar';
        });

        $response = $this->post('foo', [
            'age' => 'age',
            'make_attribute' => true
        ]);

        $response->assertJsonStructure([
            'errors' => [
                '*' => ['field', 'message']
            ]
        ]);
    }

    /** @return void */
    public function testCustomHandleFailedAuthorization()
    {
        BaseRequest::handleFailedAuthorizationUsing(function (Request $request) {
            throw new class extends Exception implements Responsable {
                public function toResponse($request)
                {
                    return new JsonResponse([
                        'code' => 'forbidden',
                        'message' => 'Forbidden'
                    ], 403);
                }
            };
        });

        Route::post('/foo', function (DummyBaseRequest $request) {
            return 'bar';
        });

        $response = $this->post('foo', ['make_unauthorized' => true]);

        $response->assertStatus(403);
        $response->assertJson([
            'code' => 'forbidden'
        ]);
        $response->assertJsonStructure([
            'code',
            'message'
        ]);
    }
}
