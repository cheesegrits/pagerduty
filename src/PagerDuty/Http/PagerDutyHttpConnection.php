<?php

namespace PagerDuty\Http;

use PagerDuty\Event;
use PagerDuty\Exceptions\PagerDutyConfigurationException;
use PagerDuty\Exceptions\PagerDutyException;

/** @noinspection PhpUnused */
class PagerDutyHttpConnection
{
    /**
     * Some default options for curl
     *
     * @var array
     */
    public static array $defaultCurlOptions = array(
        CURLOPT_SSLVERSION      => 6,
        CURLOPT_CONNECTTIMEOUT  => 10,
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_TIMEOUT         => 60,                                                                                  // maximum number of seconds to allow cURL functions to execute
        CURLOPT_USERAGENT       => 'PagerDuty-PHP-SDK',
        CURLOPT_VERBOSE         => 0,
        CURLOPT_SSL_VERIFYHOST  => 2,
        CURLOPT_SSL_VERIFYPEER  => 1,
        CURLOPT_SSL_CIPHER_LIST => 'TLSv1:TLSv1.2'
    );

    const string HEADER_SEPARATOR = ';';

    /**
     * @var string
     */
    private string $url;

    /**
     * @var array
     */
    private array $headers = [];

    /**
     * @var array
     */
    protected array $curlOptions = [];

    /**
     * @var int
     */
    protected int $responseCode;

    /**
     * PagerDutyHttpConnection constructor.
     *
     * @param  string|null  $url - PagerDuty's API
     */
    public function __construct(?string $url = null)
    {
        $url = ($url !== null) ? $url : 'https://events.pagerduty.com/v2/enqueue';

        $this->setUrl($url);
        $this->setCurlOptions(self::$defaultCurlOptions);
        $this->addHeader('Content-Type','application/json'); # assume this is the default; can override anytime

        $curl       = curl_version();
        $sslVersion = $curl['ssl_version'] ?? '';

        if ($sslVersion
            && substr_compare($sslVersion, "NSS/", 0, strlen("NSS/")) === 0)
        {
            //Remove the Cipher List for NSS
            $this->removeCurlOption(CURLOPT_SSL_CIPHER_LIST);
        }
    }

    /**
     * Set Headers
     *
     * @param  array  $headers
     * @noinspection PhpUnused
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    /**
     * Adds a Header
     *
     * @param  string  $name
     * @param  string  $value
     * @param  bool  $overWrite  allows you to override the header value
     */
    public function addHeader(string $name, string $value, bool $overWrite = true): void
    {
        if (!array_key_exists($name, $this->headers)
            || $overWrite)
        {
            $this->headers[$name] = $value;
        }
        else {
            $this->headers[$name] = $this->headers[$name] . self::HEADER_SEPARATOR . $value;
        }
    }

    /**
     * Gets all Headers
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get Header by Name
     *
     * @param  string  $name
     * @return string|null
     * @noinspection PhpUnused
     */
    public function getHeader(string $name): ?string
    {
        if (array_key_exists($name, $this->headers)) {
            return $this->headers[$name];
        }

        return null;
    }

    /**
     * Removes a Header
     *
     * @param  string  $name
     * @noinspection PhpUnused
     */
    public function removeHeader(string $name): void
    {
        unset($this->headers[$name]);
    }

    /**
     * Set service url
     *
     * @param  string  $url
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * Get Service url
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Add Curl Option
     *
     * @param  string  $name
     * @param mixed  $value
     * @noinspection PhpUnused
     */
    public function addCurlOption(string $name, mixed $value): void
    {
        $this->curlOptions[$name] = $value;
    }

    /**
     * Removes a curl option from the list
     *
     * @param  string  $name
     */
    public function removeCurlOption(string $name): void
    {
        unset($this->curlOptions[$name]);
    }

    /**
     * Set Curl Options. Overrides all curl options
     *
     * @param  array  $options
     */
    public function setCurlOptions(array $options): void
    {
        $this->curlOptions = $options;
    }

    /**
     * Gets all curl options
     *
     * @return array
     */
    public function getCurlOptions(): array
    {
        return $this->curlOptions;
    }

    /**
     * Get Curl Option by name
     *
     * @param  string  $name
     * @return mixed|null
     * @noinspection PhpUnused
     */
    public function getCurlOption(string $name): mixed
    {
        if (array_key_exists($name, $this->curlOptions)) {
            return $this->curlOptions[$name];
        }

        return null;
    }

    /**
     * Set ssl parameters for certificate-based client authentication
     *
     * @param  string  $certPath
     * @param  string|null  $passPhrase
     * @noinspection PhpUnused
     */
    public function setSSLCert(string $certPath, ?string $passPhrase = null): void
    {
        $this->curlOptions[CURLOPT_SSLCERT] = realpath($certPath);

        if ($passPhrase !== null
            && trim($passPhrase) !== '')
        {
            $this->curlOptions[CURLOPT_SSLCERTPASSWD] = $passPhrase;
        }
    }

    /**
     * Set connection timeout in seconds
     *
     * @param  integer  $timeout
     * @noinspection PhpUnused
     */
    public function setTimeout(int $timeout): void
    {
        $this->curlOptions[CURLOPT_CONNECTTIMEOUT] = $timeout;
    }

    /**
     * Set HTTP proxy information
     *
     * @param  string  $proxy
     * @throws PagerDutyConfigurationException
     * @noinspection PhpUnused
     */
    public function setProxy(string $proxy): void
    {
        $urlParts = parse_url($proxy);

        if ($urlParts === false
            || !array_key_exists('host', $urlParts))
        {
            throw new PagerDutyConfigurationException('Invalid proxy configuration ' . $proxy);
        }

        $this->curlOptions[CURLOPT_PROXY] = $urlParts['host'];

        if (isset($urlParts['port'])) {
            $this->curlOptions[CURLOPT_PROXY] .= ':' . $urlParts['port'];
        }

        if (isset($urlParts['user'])) {
            $this->curlOptions[CURLOPT_PROXYUSERPWD] = $urlParts['user'] . ':' . $urlParts['pass'];
        }
    }

    /**
     * Sets response code from curl call
     *
     * @param  int  $code
     */
    public function setResponseCode(int $code): void
    {
        $this->responseCode = $code;
    }

    /**
     * Returns response code
     *
     * @return int|null
     */
    public function getResponseCode(): ?int
    {
        return $this->responseCode;
    }

    /**
     * Sets the User-Agent string on the HTTP request
     *
     * @param  string  $userAgentString
     * @noinspection PhpUnused
     */
    public function setUserAgent(string $userAgentString): void
    {
        $this->curlOptions[CURLOPT_USERAGENT] = $userAgentString;
    }

    /**
     * Send the event to PagerDuty
     *
     * @param  Event  $payload
     * @param  array|null  $result  (Opt) (Pass by reference) - If this parameter is given, the result of the CURL call will be filled here. The response is an associative array.
     *
     * @return int|null - HTTP response code
     *  202 - Event Processed
     *  400 - Invalid Event. Throws a PagerDutyException
     *  403 - Rate Limited. Slow down and try again later.
     * @throws PagerDutyException - If status code == 400
     * @noinspection PhpUnused
     */
    public function send(Event $payload, array &$result = null): ?int
    {
        $result       = $this->post(json_encode($payload));
        $responseCode = $this->getResponseCode();

        if ($responseCode === 400) {
            throw new PagerDutyException($result['message'], $result['errors']);
        }

        return $responseCode;
    }

    /**
     * POST data to PagerDuty
     *
     * @param  string  $payload
     * @return mixed
     */
    protected function post(string $payload): mixed
    {
        $url = $this->getUrl();
        $this->addHeader('Content-Length', strlen($payload));

        $curl = curl_init($url);

        $options = $this->getCurlOptions();
        curl_setopt_array($curl, $options);

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->getHeaders());

        $response = curl_exec($curl);
        $result   = is_string($response) ? json_decode($response, true) : $response;

        $this->setResponseCode(curl_getinfo($curl, CURLINFO_HTTP_CODE));

        curl_close($curl);

        return $result;
    }
}
