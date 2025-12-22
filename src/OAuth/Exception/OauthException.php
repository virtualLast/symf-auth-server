<?php

namespace App\OAuth\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OauthException extends HttpException
{
    public function __construct(
        string $message = 'Authentication error',
        int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR,
        ?\Throwable $previous = null,
        array $headers = [],
        int $code = 0
    ) {
        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }
}
