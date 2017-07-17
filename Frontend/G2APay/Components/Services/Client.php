<?php
/*
 * (c) G2A
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Shopware\G2APay\Components\Services;

/**
 * Client for API requests.
 */
class Client
{
    const METHOD_GET    = 'GET';
    const METHOD_POST   = 'POST';
    const METHOD_PUT    = 'PUT';
    const METHOD_PATCH  = 'PATCH';
    const METHOD_DELETE = 'DELETE';

    protected $url;
    protected $method;
    protected $headers;

    protected $resource;

    /**
     * @param $url
     * @param string $method
     */
    public function __construct($url, $method = self::METHOD_GET)
    {
        $this->url     = $url;
        $this->method  = strtoupper($method);
        $this->headers = [];
    }

    /**
     * Make an API request.
     *
     * @param array $data
     * @return mixed
     */
    public function request($data = [])
    {
        $this->appendData($data);
        $this->appendHeaders();
        $response = $this->execute();
        $result   = json_decode($response, true);

        return $result;
    }

    /**
     * Add http header.
     *
     * @param $name
     * @param $value
     */
    public function addHeader($name, $value)
    {
        $this->headers[] = $name . ': ' . $value;
    }

    /**
     * Get curl resource.
     *
     * @return resource
     */
    protected function getResource()
    {
        if (is_null($this->resource)) {
            $this->resource = curl_init($this->url);
            curl_setopt($this->resource, CURLOPT_SSL_VERIFYPEER, 2);
            curl_setopt($this->resource, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($this->resource, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($this->resource, CURLOPT_VERBOSE, 0);

            if ($this->method == self::METHOD_POST) {
                curl_setopt($this->resource, CURLOPT_POST, 0);
            } elseif ($this->method != self::METHOD_GET) {
                curl_setopt($this->resource, CURLOPT_CUSTOMREQUEST, $this->method);
            }
        }

        return $this->resource;
    }

    /**
     * Append data to request.
     *
     * @param $data
     */
    protected function appendData($data)
    {
        if (empty($data)) {
            return;
        }

        $query = is_string($data) ? $data : http_build_query((array) $data);

        if ($this->method == self::METHOD_GET) {
            $join = strpos($this->url, '?') === false ? '?' : '&';
            $url  = $this->url . $join . $query;
            // update url
            curl_setopt($this->getResource(), CURLOPT_URL, $url);
        } else {
            curl_setopt($this->getResource(), CURLOPT_POSTFIELDS, $query);
        }
    }

    protected function appendHeaders()
    {
        if (empty($this->headers)) {
            return;
        }

        curl_setopt($this->getResource(), CURLOPT_HTTPHEADER, $this->headers);
    }

    /**
     * Execute request.
     *
     * @return mixed
     */
    protected function execute()
    {
        return curl_exec($this->getResource());
    }
}
