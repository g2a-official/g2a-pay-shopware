<?php
/*
 * (c) G2A
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\G2APay\Components\Gateway;
use Shopware\G2APay\Components\Helpers\SessionHelper;
use Shopware\G2APay\Controllers\Frontend\AbstractController;
use Shopware\G2APay\Components\Helpers\OrderHelper;

/**
 * Module gateway processing controller.
 */
class Shopware_Controllers_Frontend_PaymentG2APay extends AbstractController
{
    /**
     * @var Gateway
     */
    protected $gateway;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * Init controller.
     */
    public function init()
    {
        $configHelper      = $this->Plugin()->ConfigHelper();
        $this->orderHelper = $this->Plugin()->OrderHelper();
        $sessionHelper     = new SessionHelper($this->get('session'));
        $this->gateway     = new Gateway($configHelper, $this->orderHelper, $sessionHelper);
    }

    /**
     * Gateway redirect action.
     *
     * @throws Exception
     */
    public function indexAction()
    {
        $orderInfo = $this->saveBasketOrder();

        if (empty($orderInfo) || empty($orderInfo['order'])) {
            throw new Exception('Order save failed');
        }

        $this->redirect($this->gateway->getCreateQuoteRedirect($orderInfo));
    }

    /**
     * Success callback action.
     */
    public function successAction()
    {
        $orderId = $this->Request()->get('orderId');
        $token   = $this->Request()->get('token');
        try {
            $this->gateway->processSuccessRequest($orderId, $token);
            $checkToken = $this->gateway->getOrderCheckToken($orderId);
            $router     = $this->Front()->Router();

            $this->View()->assign('text_title', $this->translate('PaymentSuccessTitle', 'success'));
            $this->View()->assign('text_header', $this->translate('PaymentSuccessHeader', 'success'));
            $this->View()->assign('text_wait', $this->translate('PaymentSuccessWait', 'success'));
            $this->View()->assign('text_history', $this->translate('PaymentOrdersHistory', 'success'));
            $this->View()->assign('check_url',
                $router->assemble(['action' => 'status', 'controller' => 'payment_g2apay', 'token' => $checkToken]));
            $this->View()->assign('history_url',
                $router->assemble(['action' => 'orders', 'controller' => 'account']));
        } catch (Exception $e) {
            $this->redirect(['action' => 'orders', 'controller' => 'account']);
        }
    }

    /**
     * Failure/cancel callback action.
     */
    public function failureAction()
    {
        $orderId = $this->Request()->get('orderId');
        $token   = $this->Request()->get('token');
        try {
            $this->gateway->processCancelRequest($orderId, $token);
            $this->redirect(['action' => 'cart', 'controller' => 'checkout']);
        } catch (Exception $e) {
            $this->redirect('/');
        }
    }

    /**
     * Order status check action.
     */
    public function statusAction()
    {
        $success = false;
        $retry   = false;
        $message = '';

        $token = $this->Request()->get('token');

        try {
            $retry   = $this->gateway->canCheckOrder($token);
            $success = $this->gateway->checkOrderComplete();
            if ($success) {
                $message = $this->translate('PaymentStatusSuccess', 'success');
            } elseif (!$retry) {
                $message = $this->translate('PaymentStatusFailed', 'success');
            }
        } catch (Exception $e) {
            $success = false;
            $retry   = false;
            $message = $this->translate('PaymentStatusError', 'success');
        }
        die(json_encode(compact('success', 'retry', 'message')));
    }

    /**
     * Save basket order. It's here because
     * saveOrder belongs to parent Payment controller.
     *
     * @return array
     */
    protected function saveBasketOrder()
    {
        $uniqueId      = $this->createPaymentUniqueId();
        $transactionId = $uniqueId; //temporary transaction Id

        $orderNumber = $this->saveOrder($transactionId, $uniqueId);

        $userId = Shopware()->Session()->sUserId;

        return [
            'order'         => $this->orderHelper->getUserOrderByNumberAndPaymentId($orderNumber, $uniqueId, $userId),
            'basketContent' => $this->getBasket()['content'],
        ];
    }
}
