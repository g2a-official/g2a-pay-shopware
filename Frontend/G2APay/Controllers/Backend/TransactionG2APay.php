<?php
/*
 * (c) G2A
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\G2APay\Components\Helpers\OrderHelper;
use Shopware\G2APay\Components\Helpers\TransactionHelper;
use Shopware\G2APay\Components\Rest;

/**
 * Backend transaction controller.
 */
class Shopware_Controllers_Backend_TransactionG2APay extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @var TransactionHelper
     */
    protected $transactionHelper;

    /**
     * @var Rest
     */
    protected $rest;

    /**
     * Init controller.
     */
    public function init()
    {
        parent::init();
        $configHelper            = $this->Plugin()->ConfigHelper();
        $this->orderHelper       = $this->Plugin()->OrderHelper();
        $this->transactionHelper = $this->Plugin()->TransactionHelper();
        $this->rest              = new Rest($configHelper);
    }

    /**
     * Check if transactions tab can be displayed.
     */
    public function displayTabAction()
    {
        $orderId = $this->Request()->getParam('orderId');

        try {
            $order   = $this->orderHelper->getOrderById($orderId);
            $success = $this->orderHelper->isG2APayment($order);
        } catch (Exception $e) {
            $success = false;
        }

        $this->View()->assign(['success' => $success]);
    }

    /**
     * Load transactions for given order.
     */
    public function loadStoreAction()
    {
        try {
            $order = $this->orderHelper->getOrderById((int) $this->Request()->getParam('orderId'));
            $this->orderHelper->validateG2APayment($order);
            $data    = $this->transactionHelper->getOrderTransactions($order);
            $success = true;
        } catch (Exception $e) {
            $data    = [];
            $success = false;
        }

        $this->View()->assign(['success' => $success, 'data' => $data]);
    }

    /**
     * Get order refund info.
     */
    public function refundInfoAction()
    {
        $canRefund = false;
        $maxRefund = 0;

        try {
            $order                  = $this->orderHelper->getOrderById((int) $this->Request()->getParam('orderId'));
            $hasCompleteTransaction = $this->transactionHelper->hasOrderCompleteTransaction($order);
            if ($hasCompleteTransaction) {
                $maxRefund = $this->transactionHelper->getOrderMaxRefundAmount($order);
                $canRefund = $maxRefund > 0;
            }
            $success = true;
        } catch (Exception $e) {
            $success = false;
        }

        $this->View()->assign([
            'success'   => $success,
            'canRefund' => $canRefund,
            'maxRefund' => $maxRefund,
        ]);
    }

    /**
     * Refund order.
     */
    public function refundAction()
    {
        $amount        = (float) $this->Request()->getParam('refundAmount');
        $transactionId = $this->Request()->getParam('transactionId');
        try {
            $order = $this->orderHelper->getOrderById((int) $this->Request()->getParam('orderId'));
            $this->orderHelper->validateG2APayment($order);
            if ($amount > 0) {
                $success = $this->rest->refundOrder($order, $amount, $transactionId);
                $message = $success ? 'Refund request was sent. It may take a moment before it will be recorded'
                    : 'Online refund failed';
            } else {
                $success = false;
                $message = 'Invalid amount provided';
            }
        } catch (Exception $e) {
            $success = false;
            $message = 'Something went wrong';
        }

        $this->View()->assign(['success' => $success, 'message' => $message]);
    }

    /**
     * Get resource.
     *
     * @param string $name
     * @return mixed
     * @throws \Exception
     */
    public function get($name)
    {
        if (!version_compare(Shopware::VERSION, '4.2.0', '<') || Shopware::VERSION === '___VERSION___') {
            return Shopware()->Container()->get($name);
        }
        if ($name === 'pluginlogger') {
            $name = 'log';
        }

        return Shopware()->Bootstrap()->getResource(ucfirst($name));
    }

    /**
     * @return \Shopware_Plugins_Frontend_G2APay_Bootstrap
     */
    protected function Plugin()
    {
        /* @var  $plugin */
        return $this->get('plugins')->Frontend()->G2APay();
    }
}
