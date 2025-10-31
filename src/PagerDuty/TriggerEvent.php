<?php

namespace PagerDuty;

/**
 * A 'trigger' event
 * @link https://developer.pagerduty.com/docs/events-api-v2/trigger-events/
 *
 * @author adil
 */
class TriggerEvent extends Event
{

    /** @noinspection PhpUnused */
    const string CRITICAL = 'critical';
    const string ERROR = 'error';
    /** @noinspection PhpUnused */
    const string WARNING = 'warning';
    /** @noinspection PhpUnused */
    const string INFO = 'info';

    /**
     *
     * @var bool
     */
    private bool $autoDeDupKey;

    /**
     * Ctor
     *
     * @param string $routingKey - The routing key, taken from your PagerDuty 'Configuration' > 'Services' page
     * @param string $summary - The Error message
     * @param string $source - The unique location of the affected system, preferably a hostname or FQDN.
     * @param string $severity - One of 'critical', 'error', 'warning' or 'info'. Use the constants above
     * @param boolean $autoDeDupKey - If true, auto-generates a `dedup_key` based on the md5 hash of the $summary
     */
    public function __construct(string $routingKey, string $summary, string $source, string $severity, ?bool $autoDeDupKey = false)
    {
        parent::__construct($routingKey, 'trigger');
        $this->setPayloadSummary($summary);
        $this->setPayloadSource($source);
        $this->setPayloadSeverity($severity);

        $this->autoDeDupKey = (bool) $autoDeDupKey;
    }

    /**
     * A human-readable error message.
     * This is what PD will read over the phone.
     *
     * @param  string  $summary
     *
     * @return self
     */
    public function setPayloadSummary(string $summary): static
    {
        $this->dict['payload']['summary'] = $summary;
        return $this;
    }

    /**
     * The unique location of the affected system, preferably a hostname or FQDN.
     *
     * @param  string  $source
     * @return self
     */
    public function setPayloadSource(string $source): static
    {
        $this->dict['payload']['source'] = $source;
        return $this;
    }

    /**
     * One of critical, error, warning or info. Use the class constants above
     *
     * @param  string  $value
     * @return self
     */
    public function setPayloadSeverity(string $value): static
    {
        $this->dict['payload']['severity'] = $value;
        return $this;
    }

    /**
     * The time this error occurred.
     *
     * @param  string  $timestamp - Can be a datetime string as well. See the example @ https://v2.developer.pagerduty.com/docs/send-an-event-events-api-v2
     * @return self
     */
    public function setPayloadTimestamp(string $timestamp): static
    {
        $this->dict['payload']['timestamp'] = $timestamp;
        return $this;
    }

    /**
     * From the PD docs: "Component of the source machine that is responsible for the event, for example, `mysql` or `eth0`"
     *
     * @param  string  $value
     * @return self
     */
    public function setPayloadComponent(string $value): static
    {
        $this->dict['payload']['component'] = $value;
        return $this;
    }

    /**
     * From the PD docs: "Logical grouping of components of a service, for example, `app-stack`"
     *
     * @param  string  $value
     * @return self
     * @noinspection PhpUnused
     */
    public function setPayloadGroup(string $value): static
    {
        $this->dict['payload']['group'] = $value;
        return $this;
    }

    /**
     * From the PD docs: "The class/type of the event, for example, `ping failure` or `cpu load`"
     *
     * @param  string  $value
     * @return self
     */
    public function setPayloadClass(string $value): static
    {
        $this->dict['payload']['class'] = $value;
        return $this;
    }

    /**
     * An associative array of additional details about the event and affected system
     *
     * @param array $dict
     * @return self
     */
    public function setPayloadCustomDetails(array $dict): static
    {
        $this->dict['payload']['custom_details'] = $dict;
        return $this;
    }

    /**
     * Attach a link to the incident.
     *
     * @link https://developer.pagerduty.com/docs/events-api-v2/trigger-events/#the-links-property
     *
     * @param  string  $href URL of the link to be attached.
     * @param  string|null  $text Optional. Plain text that describes the purpose of the link and can be used as the link's text.
     *
     * @return self
     */
    public function addLink(string $href, ?string $text = null): static
    {
        if (!array_key_exists('links', $this->dict)) {
            $this->dict['links'] = [];
        }

        $link = ['href' => $href];
        if (!empty($text)) {
            $link['text'] = $text;
        }
        $this->dict['links'][] = $link;

        return $this;
    }

    /**
     * Attach an image to the incident.
     *
     * @link https://developer.pagerduty.com/docs/events-api-v2/trigger-events/#the-images-property
     *
     * @param  string  $src The source (URL) of the image being attached to the incident. This image must be served via HTTPS.
     * @param  string|null  $href Optional URL; makes the image a clickable link.
     * @param  string|null  $alt Optional alternative text for the image.
     *
     * @return self
     */
    public function addImage(string $src, ?string $href = null, ?string $alt = null): static
    {
        if (!array_key_exists('images', $this->dict)) {
            $this->dict['images'] = [];
        }

        $image = ['src' => $src];
        if (!empty($href)) {
            $image['href'] = $href;
        }
        if (!empty($alt)) {
            $image['alt'] = $alt;
        }
        $this->dict['images'][] = $image;

        return $this;
    }

    public function toArray(): array
    {
        if ($this->autoDeDupKey) {
            $this->setDeDupKey("md5-" . md5($this->dict['payload']['summary']));
        }
        return $this->dict;
    }
}
