<?php
/*
 * (c) G2A
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Shopware\G2APay\Components;

use Enlight_Controller_Router;
use Exception;
use Shopware\G2APay\Components\Helpers\OrderHelper;
use Shopware\G2APay\Components\Helpers\SessionHelper;
use Shopware\G2APay\Components\Helpers\ToolsHelper;
use Shopware\G2APay\Components\Helpers\ConfigHelper;
use Shopware\G2APay\Components\Services\Client;

/**
 * Payment gateway handling class.
 */
class Gateway
{
    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * @var Enlight_Controller_Router
     */
    protected $router;

    /**
     * @var SessionHelper
     */
    protected $sessionHelper;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @param ConfigHelper $configHelper
     * @param OrderHelper $orderHelper
     * @param SessionHelper $sessionHelper
     */
    public function __construct($configHelper, $orderHelper, $sessionHelper)
    {
        $this->configHelper  = $configHelper;
        $this->sessionHelper = $sessionHelper;
        $this->orderHelper   = $orderHelper;
        $this->router        = Shopware()->Front()->Router();
    }

    /**
     * Get create quote redirect URL.
     *
     * @param array $orderInfo
     * @return string
     */
    public function getCreateQuoteRedirect(array $orderInfo)
    {
        return $this->configHelper->getGatewayUrl($this->getCreateQuoteToken($orderInfo));
    }

    /**
     * Retrieve crete quote token for current order.
     *
     * @param array $orderInfo
     * @return mixed
     * @throws Exception
     */
    public function getCreateQuoteToken(array $orderInfo)
    {
        $order  = $orderInfo['order'];
        $basket = $orderInfo['basketContent'];

        $hash       = $order->getId() . $order->getAmount() . $order->getCurrency() . $this->configHelper->getApiSecret();
        $orderToken = $this->sessionHelper->generateOrderToken($order->getId());
        $data       = [
            'api_hash'    => $this->configHelper->getApiHash(),
            'hash'        => ToolsHelper::hash($hash),
            'order_id'    => $order->getId(),
            'amount'      => $order->getAmount(),
            'currency'    => $order->getCurrency(),
            'description' => $order->getDescription(),
            'email'       => $order->getCustomerEmail(),
            'url_failure' => $this->getFailureUrl($order->getId(), $orderToken),
            'url_ok'      => $this->getSuccessUrl($order->getId(), $orderToken),
            'items'       => $this->getOrderItems($order),
        ];

        $addresses = [
            'billing'  => $order->getBillingAddress(),
            'shipping' => $order->getShippingAddress(),
        ];

        if ($this->orderHelper->validateAddresses($addresses)) {
            $data['addresses'] = $addresses;
        }

        if ($this->isSubscription($basket)) {
            $data['subscription']              = '1';
            $data['subscription_product_name'] = 'Monthly subscription based on order #' . $order->getId();
            $data['subscription_type']         = 'product';
            $data['subscription_period']       = 'monthly';
        }

        $client = new Client($this->configHelper->getCreateQuoteUrl(), Client::METHOD_POST);
        $result = $client->request($data);

        if (empty($result['status']) || strtolower($result['status']) !== 'ok') {
            throw new Exception('Cannot get payment token');
        }

        return $result['token'];
    }

    /**
     * Returns if order should be processed as subscription.
     *
     * @param array $basketItems
     * @return bool
     */
    private function isSubscription(array $basketItems)
    {
        foreach ($basketItems as $basketItem) {
            if ($this->isBasketItemRecurring($basketItem) !== true) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verifies if product can be sold recurrently with G2A Pay.
     *
     * @param array $basketItem
     * @return bool
     */
    private function isBasketItemRecurring(array $basketItem)
    {
        foreach ($basketItem['additional_details']['sProperties'] as $sProperty) {
            if ($sProperty['value'] === OrderHelper::MONTHLY_SUBSCRIPTION_NAME) {
                return true;
            }
        }

        return false;
    }

    /**
     * Process gateway success data.
     *
     * @param $orderId
     * @param $token
     */
    public function processSuccessRequest($orderId, $token)
    {
        $this->validateOrderToken($orderId, $token);
        Shopware()->Modules()->Basket()->sRefreshBasket();
    }

    /**
     * Process gateway failure data.
     *
     * @param $orderId
     * @param $token
     * @throws Exception
     */
    public function processCancelRequest($orderId, $token)
    {
        $this->validateOrderToken($orderId, $token);
        $order = $this->orderHelper->getOrderById($orderId);
        $this->orderHelper->cancelOrder($order, 'Payment cancelled by user');
        $this->orderHelper->reorder($order);
    }

    /**
     * Generate unique token for order status request.
     *
     * @param $orderId
     * @return string
     */
    public function getOrderCheckToken($orderId)
    {
        return $this->sessionHelper->generateOrderCheckToken($orderId);
    }

    /**
     * Verify order status check token and check if attempt can be made.
     *
     * @param $token
     * @return bool
     * @throws Exception
     */
    public function canCheckOrder($token)
    {
        if (!$this->sessionHelper->isOrderCheckTokenValid($token)) {
            throw new Exception('Access denied');
        }

        return $this->sessionHelper->canCheckOrder();
    }

    /**
     * Check if order payment was complete.
     *
     * @return bool
     * @throws Exception
     */
    public function checkOrderComplete()
    {
        $orderId = $this->sessionHelper->getCheckOrderId();
        $order   = $this->orderHelper->getOrderById($orderId);
        if (is_null($order)) {
            throw new Exception('Order not found');
        }

        return $this->orderHelper->isOrderComplete($order);
    }

    /**
     * Get gateway failure url.
     *
     * @param $orderId
     * @param $token
     * @return mixed|string
     */
    protected function getFailureUrl($orderId, $token)
    {
        return $this->router->assemble([
            'controller'  => 'payment_g2apay',
            'action'      => 'failure',
            'forceSecure' => 1,
            'orderId'     => $orderId,
            'token'       => $token,
        ]);
    }

    /**
     * Get gateway success url.
     *
     * @param $orderId
     * @param $token
     * @return mixed|string
     */
    protected function getSuccessUrl($orderId, $token)
    {
        return $this->router->assemble([
            'controller'  => 'payment_g2apay',
            'action'      => 'success',
            'forceSecure' => 1,
            'orderId'     => $orderId,
            'token'       => $token,
        ]);
    }

    /**
     * Get order items data for create quote request.
     *
     * @param $order
     * @return array
     */
    protected function getOrderItems($order)
    {
        return array_map(function ($item) {
            /* @var $item \Shopware\G2APay\Components\Order\Item\ItemInterface */
            return [
                'sku'    => $item->getSku(),
                'name'   => $item->getName(),
                'amount' => $item->getAmount(),
                'qty'    => $item->getQuantity(),
                'extra'  => $item->getExtra(),
                'type'   => $item->getType(),
                'id'     => $item->getId(),
                'price'  => $item->getPrice(),
                'url'    => $item->getUrl(),
            ];
        }, $order->getItems());
    }

    /**
     * Validate gateway callback order token.
     *
     * @param $orderId
     * @param $token
     * @throws Exception
     */
    protected function validateOrderToken($orderId, $token)
    {
        $isOrderTokenValid = $this->sessionHelper->isOrderTokenValid($orderId, $token);
        $this->sessionHelper->clearOrderToken();
        if (!$isOrderTokenValid) {
            throw new Exception('Invalid token');
        }
    }
}
