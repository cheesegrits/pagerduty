<?php

namespace PagerDuty;

use ArrayAccess;
use Exception;
use JsonSerializable;
use PagerDuty\Exceptions\PagerDutyException;

/**
 * An abstract Event
 *
 * @author adil
 */
abstract class Event implements ArrayAccess, JsonSerializable
{

    protected array $dict = [];

    /**
     * ctor
     *
     * @param  string  $routingKey
     * @param  string  $type - One of 'trigger', 'acknowledge' or 'resolve'
     */
    protected function __construct(string $routingKey, string $type)
    {
        $this->dict['routing_key'] = $routingKey;
        $this->dict['event_action'] = $type;
    }

    /**
     * A unique key to identify an outage.
     * Multiple events with the same $key will be grouped into one open incident. From the PD docs :
     *
     * `Submitting subsequent events for the same `dedup_key` will result in those events being applied to an open alert
     * matching that `dedup_key`. Once the alert is resolved, any further events with the same `dedup_key` will create a
     * new alert (for `trigger` events) or be dropped (for `acknowledge` and `resolve` events).`
     *
     * @link https://v2.developer.pagerduty.com/docs/events-api-v2#alert-de-duplication
     *
     * @param  string  $key
     *
     * @return self
     */
    public function setDeDupKey(string $key): static
    {
        $this->dict['dedup_key'] = substr($key, 0, 255);
        return $this;
    }

    /**
     * Get the request JSON as an array
     * Useful for debugging or logging.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->dict ?? [];
    }

    /**
     * Send the event to PagerDuty
     *
     * @param array|null  $result (Opt) (Pass by reference) - If this parameter is given, the result of the CURL call will be filled here. The response is an associative array.
     *
     * @return int - HTTP response code
     *  202 - Event Processed
     *  400 - Invalid Event. Throws a PagerDutyException
     *  403 - Rate Limited. Slow down and try again later.
     * 
     * @throws PagerDutyException - If status code == 400
     * @noinspection PhpUnused
     */
    public function send(?array &$result = null): int
    {
        $jsonStr = json_encode($this);

        $curl = curl_init("https://events.pagerduty.com/v2/enqueue");
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonStr);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonStr),
        ));

        $result = json_decode(curl_exec($curl), true);

        $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($responseCode == 400) {
            throw new PagerDutyException($result['message'], $result['errors']);
        }

        return $responseCode;
    }
    
    /* -------- ArrayAccess -------- */

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->dict);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (!$this->offsetExists($offset)) {
            return null;
        }

        return $this->dict[$offset];
    }

    /**
     * @throws Exception
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (empty($offset) || is_string($offset)) {
            throw new Exception("Offset must be a non-empty string. It is `" . var_export($offset, true) . "`");
        }

        $this->dict[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        if ($this->offsetExists($offset)) {
            unset($this->dict[$offset]);
        }
    }
    /* -------- JsonSerializable -------- */

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
