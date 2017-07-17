<?php
/*
 * (c) G2A
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Shopware\G2APay\Components\Helpers;

use Exception;
use Shopware\G2APay\Components\Order\Item\ItemInterface;
use Shopware\G2APay\Components\Order\Order;
use Shopware\G2APay\Components\Order\OrderInterface;

/**
 * Helper for Order operations.
 */
class OrderHelper
{
    const MONTHLY_SUBSCRIPTION_NAME = 'G2A Pay Monthly Subscription';

    /**
     * @var int payment status after complete payment
     */
    protected $paymentStatusPaid;

    /**
     * @var int order status after complete payment
     */
    protected $orderStatusPending;

    /**
     * @var int order status after reject/cancel
     */
    protected $orderStatusCancelled;

    /**
     * @param \Enlight_Config $config
     */
    public function __construct($config)
    {
        $this->paymentStatusPaid    = $config->get('g2apayPaymentStatusId');
        $this->orderStatusPending   = $config->get('g2apayPendingStatusId');
        $this->orderStatusCancelled = $config->get('g2apayCancelledStatusId');
    }

    /**
     * Gets order by order number and payment number for given user.
     *
     * @param $orderNumber
     * @param $paymentNumber
     * @param $userId
     * @return null|OrderInterface
     */
    public function getUserOrderByNumberAndPaymentId($orderNumber, $paymentNumber, $userId)
    {
        $order = $this->getRepository()->findOneBy([
            'number'      => $orderNumber,
            'temporaryId' => $paymentNumber,
            'customerId'  => $userId,
        ]);

        return is_null($order) ? null : new Order($order);
    }

    /**
     * Get order by id.
     *
     * @param $orderId
     * @throws Exception
     * @return null|OrderInterface
     */
    public function getOrderById($orderId)
    {
        if (empty($orderId)) {
            throw new Exception('Missing order id');
        }

        /** @var \Shopware\Models\Order\Order $order */
        $order = $this->getRepository()->find($orderId);

        if (is_null($order)) {
            throw new Exception('Order not found');
        }

        return new Order($order);
    }

    /**
     * Returns order id.
     *
     * @param string $column
     * @param string $value
     * @return string
     */
    public function getOrderId($column, $value)
    {
        try {
            $availableColumns = ['subscription_id', 'transaction_id'];
            if (!in_array($column, $availableColumns)) {
                throw new \Exception('Invalid column name');
            }

            return Shopware()->Db()->fetchOne(Shopware()->Db()
            ->select()
            ->from(['t' => 's_g2apay_transaction'], [
                'orderId'  => 'order_id',
            ])
            ->where('t.' . $column . ' = ?', $value)
            ->limit(1));
        } catch (\Exception $e) {
        }
    }

    /**
     * Check if given order has G2A Pay payment method.
     *
     * @param OrderInterface $order
     * @return mixed
     */
    public function isG2APayment($order)
    {
        return 'g2apay' === $order->getPaymentName();
    }

    /**
     * Validate if order has a G2A Pay payment method.
     *
     * @param $order
     * @return bool
     * @throws Exception
     */
    public function validateG2APayment($order)
    {
        if (is_null($order) || !$this->isG2APayment($order)) {
            throw new Exception('Invalid payment method');
        }

        return true;
    }

    /**
     * Check if order payment is complete.
     *
     * @param OrderInterface $order
     * @return bool
     */
    public function isOrderComplete($order)
    {
        $orderStatus   = $order->getOrderStatus();
        $paymentStatus = $order->getPaymentStatus();

        return $orderStatus && $orderStatus->getId() == $this->orderStatusPending
        && $paymentStatus && $paymentStatus->getId() == $this->paymentStatusPaid;
    }

    /**
     * Recreate basket from order.
     *
     * @param $order OrderInterface
     */
    public function reorder($order)
    {
        $basket = Shopware()->Modules()->Basket();
        /** @var ItemInterface $item */
        foreach ($order->getItems() as $item) {
            if ('product' !== $item->getType()) {
                continue;
            }
            try {
                $basket->sAddArticle($item->getSku(), $item->getQuantity());
            } catch (Exception $e) {
            }
        }
    }

    /**
     * Update order transaction id and save order.
     *
     * @param OrderInterface $order
     * @param $transactionId
     */
    public function updateOrderTransactionId($order, $transactionId)
    {
        if ($order->getPaymentStatus()->getID() !== $this->paymentStatusPaid) {
            $order->setTransactionId($transactionId);
            $order->save();
        }
    }

    /**
     * Complete order.
     *
     * @param OrderInterface $order
     * @param null $message
     */
    public function completeOrder($order, $message = null)
    {
        $this->updateOrderPaymentStatus($order, $this->paymentStatusPaid, $message);
        $this->updateOrderStatus($order, $this->orderStatusPending, $message, true);
        $this->markOrderAsComplete($order, $message);
    }

    /**
     * Cancel order.
     *
     * @param OrderInterface $order
     * @param null $message
     */
    public function cancelOrder($order, $message = null)
    {
        $this->updateOrderStatus($order, $this->orderStatusCancelled, $message);
        $this->addOrderPaymentMessage($order, $message);
    }

    /**
     * Reject order.
     *
     * @param $order
     * @param null $message
     */
    public function rejectOrder($order, $message = null)
    {
        $this->updateOrderStatus($order, $this->orderStatusCancelled, $message);
        $this->addOrderPaymentMessage($order, $message);
    }

    /**
     * Refund order.
     *
     * @param $order
     * @param $amount
     * @param null $message
     */
    public function refundOrder($order, $amount, $message = null)
    {
        $this->addOrderPaymentMessage($order, $message);
    }

    /**
     * Update order payment status and refresh order.
     *
     * @param OrderInterface $order
     * @param $status
     * @param null $message
     */
    protected function updateOrderPaymentStatus($order, $status, $message = null)
    {
        $orderModule = Shopware()->Modules()->Order();
        $orderModule->setPaymentStatus($order->getId(), $status, false, $message);
        $order->refresh(); // order needs refresh; was modified by Order module
    }

    /**
     * Update order status and refresh order.
     *
     * @param OrderInterface $order
     * @param $status
     * @param null $message
     * @param bool $sendStatusEmail
     */
    protected function updateOrderStatus($order, $status, $message = null, $sendStatusEmail = false)
    {
        $orderModule = Shopware()->Modules()->Order();
        $orderModule->setOrderStatus($order->getId(), $status, $sendStatusEmail, $message);
        $order->refresh(); // order needs refresh; was modified by Order module
    }

    /**
     * Add payment order message and save.
     *
     * @param OrderInterface $order
     * @param $message
     */
    protected function addOrderPaymentMessage($order, $message)
    {
        $order->appendMessage($message);
        $order->save();
    }

    /**
     * Mark order sa complete with given message and save it.
     *
     * @param OrderInterface $order
     * @param $message
     */
    protected function markOrderAsComplete($order, $message)
    {
        $order->appendMessage($message);
        $order->complete();
        $order->save();
    }

    /**
     * Get Order Repository.
     *
     * @return \Shopware\Components\Model\ModelRepository
     */
    protected function getRepository()
    {
        return Shopware()->Models()->getRepository('Shopware\Models\Order\Order');
    }

    /**
     * Validates if addresses array have values in all required fields.
     *
     * @param $addresses
     * @return bool
     */
    public function validateAddresses($addresses)
    {
        if (empty($addresses['billing']) || empty($addresses['shipping'])) {
            return false;
        }

        return ($this->validateAddressType($addresses['billing']) === true
            && $this->validateAddressType($addresses['shipping']) === true);
    }

    /**
     * Validate address array.
     *
     * @param array $address
     * @return bool
     */
    private function validateAddressType(array $address)
    {
        $requiredFields = ['firstname', 'lastname', 'line_1', 'zip_code', 'city', 'county', 'country'];

        foreach ($requiredFields as $requiredField) {
            if (empty($address[$requiredField])) {
                return false;
            }
        }

        return true;
    }
}
