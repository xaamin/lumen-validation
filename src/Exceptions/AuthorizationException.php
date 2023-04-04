<?php

namespace Lumen\Validation\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Support\Responsable;

class AuthorizationException extends Exception implements Responsable
{
    public function toResponse($request)
    {
        return new JsonResponse([
            'code' => 'access_denied',
            'message' => $this->getMessage()
        ], 403);
    }
}
