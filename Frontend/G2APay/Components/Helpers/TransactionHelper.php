<?php
/*
 * (c) G2A
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Shopware\G2APay\Components\Helpers;

use Shopware\G2APay\Components\Order\OrderInterface;
use Zend_Db_Expr;

/**
 * Helper for Transactions operations.
 */
class TransactionHelper
{
    /**
     * Fetch all order Transactions.
     *
     * @param OrderInterface $order
     * @return array
     */
    public function getOrderTransactions($order)
    {
        $select = $this->getDb()
            ->select()
            ->from(['t' => 's_g2apay_transaction'], [
                'transactionId'  => 'transaction_id',
                'status'         => 'status',
                'refundedAmount' => 'refunded_amount',
                'createTime'     => 'create_time',
                'isSubscription' => 'is_subscription',
                'subscriptionId' => 'subscription_id',
            ])
            ->where('t.order_id = ?', $order->getId())
            ->order('t.create_time DESC');

        return $this->getDb()->fetchAll($select);
    }

    /**
     * Check if order has a complete transaction.
     *
     * @param OrderInterface $order
     * @return bool
     */
    public function hasOrderCompleteTransaction($order)
    {
        $select = $this->getDb()
            ->select()
            ->from(['t' => 's_g2apay_transaction'], [
                'transactionsCount' => new Zend_Db_Expr('COUNT(status)'),
            ])
            ->where('t.order_id = ?', $order->getId())
            ->where('t.status = ?', 'complete');

        return (int) $this->getDb()->fetchOne($select) > 0;
    }

    /**
     * Get already refunded recorded amount for order.
     *
     * @param OrderInterface $order
     * @return float
     */
    public function getOrderAlreadyRefundedAmount($order)
    {
        $select = $this->getDb()
            ->select()
            ->from(['t' => 's_g2apay_transaction'], [
                'totalRefunded' => new Zend_Db_Expr('SUM(refunded_amount)'),
            ])
            ->where('t.order_id = ?', $order->getId());

        return (float) $this->getDb()->fetchOne($select);
    }

    /**
     * @param OrderInterface $order
     * @return int
     */
    public function getAllPayments($order)
    {
        $select = $this->getDb()
            ->select()
            ->from(['t' => 's_g2apay_transaction'], [
                'totalPaid' => new Zend_Db_Expr('count(order_id)'),
            ])
            ->where('t.order_id = ?', $order->getId())
            ->where('t.status = ?', 'complete');

        return (int) $this->getDb()->fetchOne($select) * $order->getAmount();
    }

    /**
     * Get maximum order allowed refund amount.
     *
     * @param OrderInterface $order
     * @return float
     */
    public function getOrderMaxRefundAmount($order)
    {
        return ToolsHelper::roundAmount($this->getAllPayments($order) - $this->getOrderAlreadyRefundedAmount($order));
    }

    /**
     * Create new transaction for order.
     *
     * @param OrderInterface $order
     * @param $transactionId
     * @param $status
     * @param $subscriptionId
     * @param int $refundedAmount
     * @return int
     * @throws \Zend_Db_Adapter_Exception
     */
    public function addOrderTransaction($order, $transactionId, $status, $subscriptionId, $refundedAmount = 0)
    {
        return $this->getDb()->insert('s_g2apay_transaction',
            [
                'order_id'        => $order->getId(),
                'transaction_id'  => $transactionId,
                'status'          => $status,
                'refunded_amount' => $refundedAmount,
                'create_time'     => new Zend_Db_Expr('NOW()'),
                'subscription_id' => $subscriptionId,
                'is_subscription' => empty($subscriptionId) ? false : true,
            ]);
    }

    /**
     * Get DB adapter instance.
     *
     * @return \Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    protected function getDb()
    {
        return Shopware()->Db();
    }
}
