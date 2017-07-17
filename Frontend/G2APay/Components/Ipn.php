<?php
/*
 * (c) G2A
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Shopware\G2APay\Components;

use Shopware\G2APay\Components\Helpers\ConfigHelper;
use Shopware\G2APay\Components\Helpers\OrderHelper;
use Shopware\G2APay\Components\Helpers\ToolsHelper;
use Shopware\G2APay\Components\Helpers\TransactionHelper;
use Shopware\G2APay\Components\Order\OrderInterface;
use Exception;

/**
 * IPN handling class.
 */
class Ipn
{
    /**
     * G2A Pay IPN data template.
     *
     * @var array
     */
    protected static $IPN_DATA_TEMPLATE = [
        'type'             => null,
        'subscriptionId'   => null,
        'subscriptionName' => null,
        'transactionId'    => null,
        'userOrderId'      => null,
        'amount'           => 0,
        'currency'         => null,
        'status'           => null,
        'orderCreatedAt'   => null,
        'orderCompleteAt'  => null,
        'refundedAmount'   => null,
        'hash'             => null,
    ];

    /**
     * G2A Pay IPN allowed statuses.
     *
     * @var array
     */
    protected static $IPN_STATUSES = ['complete', 'partial_refunded', 'refunded', 'rejected', 'canceled'];

    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @var TransactionHelper
     */
    protected $transactionHelper;

    /**
     * @param ConfigHelper $configHelper
     * @param OrderHelper $orderHelper
     * @param TransactionHelper $transactionHelper
     */
    public function __construct($configHelper, $orderHelper, $transactionHelper)
    {
        $this->configHelper      = $configHelper;
        $this->orderHelper       = $orderHelper;
        $this->transactionHelper = $transactionHelper;
    }

    /**
     * Filter IPN data.
     *
     * @param $data
     * @return array
     */
    public function filterIpnData($data)
    {
        $data = array_merge(static::$IPN_DATA_TEMPLATE, $data);

        return array_intersect_key((array) $data, static::$IPN_DATA_TEMPLATE);
    }

    /**
     * Validate IPN secret parameter.
     *
     * @param $secret
     * @throws Exception
     */
    public function validateIpnSecret($secret)
    {
        if ($this->configHelper->hasIpnSecret() && $this->configHelper->getIpnSecret() !== $secret) {
            throw new Exception('Invalid IPN secret');
        }
    }

    /**
     * Process IPN request data.
     *
     * @param OrderInterface $order
     * @param $data
     */
    public function processIpnData($order, $data)
    {
        $this->validateIpnHash($order, $data);
        $this->validateIpnData($order, $data);
        $this->updateOrderStatus($order, $data);
        $this->recordOrderTransaction($order, $data);
    }

    /**
     * Validate IPN request hash.
     * @param $order
     * @param $data
     * @throws Exception
     */
    protected function validateIpnHash($order, $data)
    {
        if (empty($data['hash']) || $data['hash'] !== $this->generateValidIpnHash($order, $data['transactionId'])) {
            throw new Exception('Invalid IPN hash');
        }
    }

    /**
     * Validate IPN request data.
     *
     * @param OrderInterface $order
     * @param $data
     * @throws Exception
     */
    protected function validateIpnData($order, $data)
    {
        if ($order->getId() != $data['userOrderId']) {
            throw new Exception('Order id does not match');
        }

        if ($order->getAmount() != $data['amount']) {
            throw new Exception('Invalid amount');
        }

        if ($order->getCurrency() != $data['currency']) {
            throw new Exception('Invalid currency');
        }

        if (!in_array(strtolower((string) $data['status']), static::$IPN_STATUSES)) {
            throw new Exception('Unknown status');
        }
    }

    /**
     * Generates valid IPN request hash.
     *
     * @param OrderInterface $order
     * @param $transactionId
     * @return string
     */
    protected function generateValidIpnHash($order, $transactionId)
    {
        $orderId = null;

        if ($order->getTransactionId() === $transactionId
            || $order->getPaymentStatus()->getID() !== $this->configHelper->getPaymentCompleteStatusId()) {
            $orderId = $order->getId();
        }

        return ToolsHelper::hash($transactionId . $orderId . $order->getAmount() . $this->configHelper->getApiSecret());
    }

    /**
     * Update requested order status.
     *
     * @param $order
     * @param $data
     */
    protected function updateOrderStatus($order, $data)
    {
        $status        = strtolower($data['status']);
        $transactionId = $data['transactionId'];

        $this->orderHelper->updateOrderTransactionId($order, $transactionId);

        switch ($status) {
            case 'complete':
                $message = sprintf('IPN update: order completed with transaction id: %s', $transactionId);
                $this->orderHelper->completeOrder($order, $message);
                break;
            case 'rejected':
                $message = sprintf('IPN update: order rejected with transaction id: %s', $transactionId);
                $this->orderHelper->rejectOrder($order, $message);
                break;
            case 'canceled':
                $message = sprintf('IPN update: order cancelled with transaction id: %s', $transactionId);
                $this->orderHelper->cancelOrder($order, $message);
                break;
            case 'refunded':
            case 'partial_refunded':
                $refundedAmount = $data['refundedAmount'];
                $message        = sprintf('IPN update: order refunded with transaction id: %s, amount: %.2f', $transactionId,
                    $refundedAmount);
                $this->orderHelper->refundOrder($order, $refundedAmount, $message);
                break;
        }
    }

    /**
     * @param OrderInterface $order
     * @param $data
     * @return bool
     */
    protected function recordOrderTransaction($order, $data)
    {
        $transactionId  = $data['transactionId'];
        $status         = $data['status'];
        $refundedAmount = (float) $data['refundedAmount'];
        $subscriptionId = empty($data['subscriptionId']) ? null : $data['subscriptionId'];

        return $this->transactionHelper
            ->addOrderTransaction($order, $transactionId, $status, $subscriptionId, $refundedAmount);
    }

    /**
     * @param $subscription_id
     * @param $subscription_name
     * @param $amount
     * @return string
     */
    public function generateSubscriptionHash($subscription_id, $subscription_name, $amount)
    {
        return ToolsHelper::hash($subscription_id . $amount . $subscription_name . $this->configHelper->getApiSecret());
    }
}
