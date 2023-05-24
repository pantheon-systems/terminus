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
     * @param mixed $offset
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return isset($this->{$offset});
    }

    /**
     * @param mixed $offset
     * @return mixed|null
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->{$offset} ?? null;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $this->{$offset} = $value;
    }

    /**
     * @param mixed $offset
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
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
