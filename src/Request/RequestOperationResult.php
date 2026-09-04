<?php

declare(strict_types=1);

namespace Pantheon\Terminus\Request;

/**
 * Class RequestOperationResult
 * @package Pantheon\Terminus\Request
 */
final class RequestOperationResult implements \ArrayAccess
{
    /**
     * @var mixed
     */
    private mixed $data = null;

    /**
     * @var array
     */
    private array $headers = [];

    /**
     * @var int
     */
    private int $status_code = -1;

    /**
     * @var string
     */
    private string $status_code_reason = '';

    /**
     * RequestOperationResult constructor.
     * @param array $incoming
     */
    public function __construct(
        array $incoming = [
            'data' => null,
            'headers' => [],
            'status_code' => -1,
            'status_code_reason' => '',
        ]
    ) {
        [
            'data' => $data,
            'headers' => $headers,
            'status_code' => $status_code,
            'status_code_reason' => $status_code_reason
        ] = $incoming;
        $this->setData($data);
        $this->setHeaders($headers);
        $this->setStatusCode($status_code);
        $this->setStatusCodeReason($status_code_reason);
    }

    /**
     * @return string
     */
    public function getStatusCodeReason(): string
    {
        return $this->status_code_reason;
    }

    /**
     * @param string $status_code_reason
     */
    public function setStatusCodeReason(string $status_code_reason): void
    {
        $this->status_code_reason = $status_code_reason;
    }

    /**
     * Check if an offset exists.
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return match ($offset) {
            'data' => true,
            'headers' => true,
            'status_code' => true,
            'status_code_reason' => true,
            default => false,
        };
    }

    /**
     * Get value at offset.
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        return match ($offset) {
            'data' => $this->data,
            'headers' => $this->headers,
            'status_code' => $this->status_code,
            'status_code_reason' => $this->status_code_reason,
            default => null,
        };
    }

    /**
     * Set value at offset.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        match ($offset) {
            'data' => $this->data = $value,
            'headers' => $this->headers = $value,
            'status_code' => $this->status_code = $value,
            'status_code_reason' => $this->status_code_reason = $value,
            default => null,
        };
    }

    /**
     * Unset value at offset.
     *
     * @param mixed $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        match ($offset) {
            'data' => $this->data = null,
            'headers' => $this->headers = [],
            'status_code' => $this->status_code = -1,
            'status_code_reason' => $this->status_code_reason = '',
            default => null,
        };
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        if (is_array($this->data)) {
            return json_encode($this->data, JSON_PRETTY_PRINT);
        }
        return $this->data ?? '';
    }

    /**
     * @return mixed
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData(mixed $data = null): void
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->status_code;
    }

    /**
     * @param int $status_code
     */
    public function setStatusCode(int $status_code): void
    {
        $this->status_code = $status_code;
    }

    /**
     * Any status <= 199 or >=300 is an error status.
     *
     * @return bool
     */
    public function isError(): bool
    {
        return !((bool) preg_match('/^2\d{2}$/', (string) $this->getStatusCode()));
    }
}
