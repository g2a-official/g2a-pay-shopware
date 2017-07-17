<?php
/*
 * (c) G2A
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\G2APay\Components\Ipn;
use Shopware\G2APay\Controllers\Frontend\AbstractController;
use Shopware\G2APay\Components\Helpers\OrderHelper;

/**
 * IPN action controller.
 */
class Shopware_Controllers_Frontend_IpnG2APay extends AbstractController
{
    const IPN_PAYMENT_TYPE_NAME               = 'payment';
    const IPN_SUBSCRIPTION_PAYMENT_TYPE_NAME  = 'subscription_payment';
    const IPN_SUBSCRIPTION_CREATED_TYPE_NAME  = 'subscription_created';
    const IPN_SUBSCRIPTION_CANCELED_TYPE_NAME = 'subscription_canceled';

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @var Ipn
     */
    protected $ipn;

    /**
     * @var \Shopware\G2APay\Components\Order\Order
     */
    protected $order;

    /**
     * Init controller.
     */
    public function init()
    {
        $configHelper      = $this->Plugin()->ConfigHelper();
        $this->orderHelper = $this->Plugin()->OrderHelper();
        $transactionHelper = $this->Plugin()->TransactionHelper();
        $this->ipn         = new Ipn($configHelper, $this->orderHelper, $transactionHelper);
    }

    public function dispatch($action)
    {
        if (!($this->Request()->isDispatched() && !$this->Response()->isRedirect())) {
            return;
        }
        $action_name = $this->Front()->Dispatcher()->getFullActionName($this->Request());
        if (!$event = Shopware()->Events()->notifyUntil(
                __CLASS__ . '_' . $action_name,
                ['subject' => $this]
            )) {
            $this->$action();
        }
    }

    /**
     * Process IPN request controller.
     */
    public function processAction()
    {
        $post = $this->Request()->getPost();
        $data = $this->ipn->filterIpnData($post);
        if (empty($data['userOrderId']) && !empty($data['subscriptionId'])) {
            $data['userOrderId'] = $this->orderHelper->getOrderId('subscription_id', $data['subscriptionId']);
        }
        if (empty($data['userOrderId'])) {
            $data['userOrderId'] = $this->orderHelper->getOrderId('transaction_id', $data['transactionId']);
        }
        $this->order = $this->orderHelper->getOrderById($data['userOrderId']);

        $this->ipn->validateIpnSecret($this->Request()->get('secret'));

        switch ($data['type']) {
            case self::IPN_PAYMENT_TYPE_NAME :
                die($this->processIpnPayment($data));
            case self::IPN_SUBSCRIPTION_CREATED_TYPE_NAME:
                die($this->processIpnForSubscriptionCreation($data));
            case self::IPN_SUBSCRIPTION_CANCELED_TYPE_NAME:
                die($this->processIpnForSubscriptionCancellation($data));
            default :
                die('Unrecognized ipn type');
        }
    }

    /**
     * Process action for ipn type payment.
     *
     * @param $data
     * @return string
     */
    private function processIpnPayment($data)
    {
        try {
            $this->ipn->processIpnData($this->order, $data);
        } catch (\Exception $e) {
            $this->get('pluginlogger')->error($e->getMessage(), $data);

            return 'Something went wrong: ' . $e->getMessage();
        }

        return 'ok';
    }

    /**
     * Process ipn action for subscription creation.
     *
     * @param $data
     * @return string
     */
    private function processIpnForSubscriptionCreation($data)
    {
        if ($data['hash'] !== $this->ipn->generateSubscriptionHash($data['subscriptionId'],
                $data['subscriptionName'], $data['amount'])) {
            return 'Invalid hash';
        }

        try {
            Shopware()->Db()->update('s_g2apay_transaction', [
                'is_subscription' => true,
                'subscription_id' => $data['subscriptionId'],
            ], ['transaction_id = ?' => $data['transactionId']]);

            $this->order->appendMessage('IPN update: Subscription created');
            $this->order->save();
        } catch (\Exception $e) {
            $this->get('pluginlogger')->error($e->getMessage(), $data);

            return 'Something went wrong: ' . $e->getMessage();
        }

        return 'ok';
    }

    /**
     * Process ipn action for subscription cancellation.
     *
     * @param $data
     * @return string
     */
    private function processIpnForSubscriptionCancellation($data)
    {
        if ($data['hash'] !== $this->ipn->generateSubscriptionHash($data['subscriptionId'],
                $data['subscriptionName'], $data['amount'])) {
            return 'Invalid hash';
        }

        try {
            Shopware()->Db()->update('s_g2apay_transaction', [
                'is_subscription' => false,
            ], [
                'transaction_id = ?' => $data['transactionId'],
            ]);

            $this->order->appendMessage('IPN update: Subscription cancelled');
            $this->order->save();
        } catch (\Exception $e) {
            $this->get('pluginlogger')->error($e->getMessage(), $data);

            return 'Something went wrong: ' . $e->getMessage();
        }

        return 'ok';
    }
}
