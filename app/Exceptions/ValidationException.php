<?php

namespace App\Exceptions;

/**
 * Thrown for expected, user-facing validation failures (missing fields,
 * not-found records, business-rule violations). Its message is always
 * safe to show to the client, unlike a generic \Exception/\Throwable
 * whose message may leak internals (SQL, file paths) and gets sanitized
 * in production by BaseController::jsonError()/flashError().
 */
class ValidationException extends \RuntimeException
{
}
