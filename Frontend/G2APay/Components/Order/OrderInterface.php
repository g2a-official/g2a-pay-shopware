<?php
/*
 * (c) G2A
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Shopware\G2APay\Components\Order;

/**
 * Payment order interface.
 */
interface OrderInterface
{
    /**
     * Get order unique ID.
     *
     * @return mixed
     */
    public function getId();

    /**
     * Get order total amount.
     *
     * @return float
     */
    public function getAmount();

    /**
     * Get order currency symbol.
     *
     * @return string
     */
    public function getCurrency();

    /**
     * Get order additional description.
     *
     * @return string|null
     */
    public function getDescription();

    /**
     * Get customer email address.
     *
     * @return string
     */
    public function getCustomerEmail();

    /**
     * Get order items.
     *
     * @return array
     */
    public function getItems();

    /**
     * Set transaction id.
     *
     * @param $transactionId
     * @return mixed
     */
    public function setTransactionId($transactionId);

    /**
     * Get transaction id.
     *
     * @return string|null
     */
    public function getTransactionId();

    /**
     * Get order status.
     *
     * @return mixed
     */
    public function getOrderStatus();

    /**
     * Get order payment status.
     *
     * @return mixed
     */
    public function getPaymentStatus();

    /**
     * Get order payment name.
     *
     * @return string
     */
    public function getPaymentName();

    /**
     * Append order comment.
     *
     * @param $message
     * @return mixed
     */
    public function appendMessage($message);

    /**
     * Set all parameters required for order completion.
     *
     * @return mixed
     */
    public function complete();

    /**
     * Update order record in database.
     *
     * @return mixed
     */
    public function save();

    /**
     * Reload order record from database.
     *
     * @return mixed
     */
    public function refresh();

    /**
     * Returns array with billing address information.
     * 
     * @return array
     */
    public function getBillingAddress();

    /**
     * Returns array with shipping address information.
     *
     * @return array
     */
    public function getShippingAddress();
}
