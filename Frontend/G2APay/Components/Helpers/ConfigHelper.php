<?php
/*
 * (c) G2A
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Shopware\G2APay\Components\Helpers;

/**
 * Api and Ipn configuration wrapper.
 */
class ConfigHelper
{
    /**
     * Default fallback environment.
     */
    const DEFAULT_ENVIRONMENT = 'production';

    /**
     * @var array allowed environments
     */
    protected static $ENVIRONMENTS = ['production', 'sandbox'];

    /**
     * @var array gateway urls grouped by environment
     */
    protected static $GATEWAY_URLS = [
        'production' => 'https://checkout.pay.g2a.com/index/gateway',
        'sandbox'    => 'https://checkout.test.pay.g2a.com/index/gateway',
    ];

    /**
     * @var array create quote urls grouped by environment
     */
    protected static $QUOTE_URLS = [
        'production' => 'https://checkout.pay.g2a.com/index/createQuote',
        'sandbox'    => 'https://checkout.test.pay.g2a.com/index/createQuote',
    ];

    /**
     * @var array REST base urls grouped by environment
     */
    protected static $REST_BASE_URLS = [
        'production' => 'https://pay.g2a.com/rest',
        'sandbox'    => 'https://www.test.pay.g2a.com/rest',
    ];

    protected $apiHash;
    protected $apiSecret;
    protected $merchantEmail;
    protected $environment;
    protected $ipnSecret;
    protected $paymentCompleteStatusId;

    /**
     * Get environments list.
     *
     * @return array
     */
    public static function getEnvironments()
    {
        return static::$ENVIRONMENTS;
    }

    /**
     * @param \Enlight_Config $config
     */
    public function __construct($config)
    {
        $this->apiHash                 = $config->get('g2apayApiHash');
        $this->apiSecret               = $config->get('g2apayApiSecret');
        $this->merchantEmail           = $config->get('g2apayMerchantEmail');
        $this->ipnSecret               = $config->get('g2apayIpnSecret');
        $this->paymentCompleteStatusId = $config->get('g2apayPaymentStatusId');

        $environment = $config->get('g2apayEnvironment');

        if (!in_array($environment, static::$ENVIRONMENTS)) {
            $environment = static::DEFAULT_ENVIRONMENT;
        }

        $this->environment = $environment;
    }

    /**
     * Get API Hash.
     *
     * @return string
     */
    public function getApiHash()
    {
        return $this->apiHash;
    }

    /**
     * Get API Secret.
     *
     * @return string
     */
    public function getApiSecret()
    {
        return $this->apiSecret;
    }

    /**
     * Get Merchant Email.
     *
     * @return string
     */
    public function getMerchantEmail()
    {
        return $this->merchantEmail;
    }

    /**
     * Get authorization for current configuration.
     *
     * @return string
     */
    public function getAuthorizationHash()
    {
        return ToolsHelper::hash($this->apiHash . $this->merchantEmail . $this->apiSecret);
    }

    /**
     * Returns Create Quote url dependent on current environment.
     *
     * @return string
     */
    public function getCreateQuoteUrl()
    {
        return self::$QUOTE_URLS[$this->environment];
    }

    /**
     * Returns Gateway url dependent on current environment
     * With additional $token.
     *
     * @param string $token
     * @return string
     */
    public function getGatewayUrl($token)
    {
        return self::$GATEWAY_URLS[$this->environment] . '?' . http_build_query(compact('token'));
    }

    /**
     * Get REST full url for given path.
     *
     * @param string $path
     * @return string
     */
    public function getRestUrl($path = '')
    {
        return self::$REST_BASE_URLS[$this->environment] . '/' . ltrim($path, '/');
    }

    /**
     * Check if IPN secret was setup.
     *
     * @return bool
     */
    public function hasIpnSecret()
    {
        return !empty($this->ipnSecret);
    }

    /**
     * Get IPN secret.
     *
     * @return mixed
     */
    public function getIpnSecret()
    {
        return $this->ipnSecret;
    }

    /**
     * Get Payment Complete Status.
     *
     * @return mixed
     */
    public function getPaymentCompleteStatusId()
    {
        return $this->paymentCompleteStatusId;
    }
}
