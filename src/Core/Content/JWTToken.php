<?php declare(strict_types=1);

namespace Wexo\EasyTranslate\Core\Content;

use DateTime;

class JWTToken
{
    protected object $headers;
    protected object $payload;

    public function __construct(object $headers = null, object $payload = null)
    {
        $this->headers = $headers;
        $this->payload = $payload;
    }

    /**
     * @return object
     */
    public function getHeaders(): object
    {
        return $this->headers;
    }

    /**
     * @param object $headers
     */
    public function setHeaders(object $headers): void
    {
        $this->headers = $headers;
    }

    /**
     * @return object
     */
    public function getPayload(): object
    {
        return $this->payload;
    }

    /**
     * @param object $payload
     */
    public function setPayload(object $payload): void
    {
        $this->payload = $payload;
    }

    public function getExpiresAt(): DateTime
    {
        return DateTime::createFromFormat('U.u', strval($this->payload->exp));
    }
}
