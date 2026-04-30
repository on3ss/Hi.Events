<?php

namespace HiEvents\Exceptions\Razorpay;

use RuntimeException;
use Razorpay\Api\Errors\Error;

class RazorpayAccountSetupException extends RuntimeException
{
    private array $razorpayErrorDetails;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        array $razorpayErrorDetails = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->razorpayErrorDetails = $razorpayErrorDetails;
    }

    public function getRazorpayErrorDetails(): array
    {
        return $this->razorpayErrorDetails;
    }

    public static function fromRazorpayError(Error $error, string $context = ''): self
    {
        return new self(
            message: "Razorpay error during {$context}: {$error->getMessage()}",
            code: $error->getCode(),
            previous: $error,
            razorpayErrorDetails: [
                'description' => $error->getMessage(),
                'code'        => $error->getCode(),
            ]
        );
    }
}