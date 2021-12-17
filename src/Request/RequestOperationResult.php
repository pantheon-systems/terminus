<?php


namespace Pantheon\Terminus\Request;

/**
 * Class RequestOperationResult
 * @package Pantheon\Terminus\Request
 */
final class RequestOperationResult implements \ArrayAccess
{
    /**
     * @var
     */
    private $data;
    /**
     * @var array
     */
    private array $headers = [];
    /**
     * @var int
     */
    private int $status_code = -1;

    private string $status_code_reason;


    /**
     * RequestOperationResult constructor.
     * @param array $incoming
     */
    public function __construct(
        array $incoming = [
            'data' => null,
            'headers' => [],
            'status_code' => -1
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
     * @inheritdoc
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->{$offset});
    }

    /**
     * @inheritdoc
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->{$offset} ?? null;
    }

    /**
     * @inheritdoc
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->{$offset} = $value;
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->{$offset});
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
     * @return null | string | array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData($data = null): void
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
        return !(bool) preg_match('/^2\d{2}$/', $this->getStatusCode());
    }
}
