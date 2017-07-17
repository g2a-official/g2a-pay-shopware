<?php
/*
 * (c) G2A
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Shopware\G2APay\Components;

use Shopware\G2APay\Components\Helpers\ConfigHelper;
use Shopware\G2APay\Components\Helpers\ToolsHelper;
use Shopware\G2APay\Components\Services\Client;

/**
 * REST requests handling class.
 */
class Rest
{
    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    public function __construct($configHelper)
    {
        $this->configHelper = $configHelper;
    }

    /**
     * @param $order
     * @param $amount
     * @param $transactionId
     * @return bool
     */
    public function refundOrder($order, $amount, $transactionId)
    {
        $path = 'transactions/' . $transactionId;
        $url  = $this->configHelper->getRestUrl($path);

        $amount = ToolsHelper::roundAmount($amount);

        $orderId = $transactionId === $order->getTransactionId() ? $order->getId() : null;

        $hash = $transactionId . $orderId . ToolsHelper::roundAmount($order->getAmount())
            . ToolsHelper::roundAmount($amount) . $this->configHelper->getApiSecret();
        $data = [
            'action' => 'refund',
            'amount' => $amount,
            'hash'   => ToolsHelper::hash($hash),
        ];
        $client = $this->createRestClient($url, Client::METHOD_PUT);
        $result = $client->request($data);

        return is_array($result) && isset($result['status']) && strcasecmp('ok', $result['status']) === 0;
    }

    /**
     * Create REST client.
     *
     * @param $url
     * @param $method
     * @return Client
     */
    protected function createRestClient($url, $method)
    {
        $client = new Client($url, $method);
        $client->addHeader('Authorization', $this->configHelper->getApiHash() . ';'
            . $this->configHelper->getAuthorizationHash());

        return $client;
    }
}
