<?php

declare(strict_types=1);

namespace App\DTO;

use App\Exception\BaseException;
use Throwable;

readonly class ChatCommandResult
{
    public function __construct(
        public bool $isOk,
        public bool $isFinallyFailed = false,
        private ?string $error = null
    ) {}

    public static function fatal(Throwable $e): self
    {
        return self::fail($e->getMessage(), true);
    }

    public static function fail(
        string $error,
        bool $isFinallyFailed = false
    ): self {
        return new self(
            false,
            $isFinallyFailed,
            $error
        );
    }

    public static function success(): self
    {
        return new self(true);
    }

    /** @throws BaseException */
    public function getError(): string
    {
        if ($this->error === null || $this->error === '') {
            throw new BaseException(
                'ChatCommandResult::getError: error is empty'
            );
        }

        return $this->error;
    }
}
