<?php
/*
 * (c) G2A
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Shopware\G2APay\Components\Helpers;

use Shopware\Components\Random;

/**
 * Helper for session operations.
 */
class SessionHelper
{
    /**
     * @var \Enlight_Components_Session_Namespace
     */
    protected $session;

    /**
     * @param \Enlight_Components_Session_Namespace $session
     */
    public function __construct($session)
    {
        $this->session = $session;
    }

    /**
     * Generate random secure token for orderId.
     *
     * @param $orderId
     * @return string
     */
    public function generateOrderToken($orderId)
    {
        $token                          = Random::getAlphanumericString(32);
        $this->session->g2apayOrderId   = $orderId;
        $this->session->g2apayOrdeToken = $token;

        return $token;
    }

    /**
     * Check if random secure token matches stored one.
     *
     * @param $orderId
     * @param $token
     * @return bool
     */
    public function isOrderTokenValid($orderId, $token)
    {
        return isset($this->session->g2apayOrderId) && isset($this->session->g2apayOrdeToken)
        && $this->session->g2apayOrderId == $orderId && $this->session->g2apayOrdeToken === $token;
    }

    /**
     * Clear order secure token.
     */
    public function clearOrderToken()
    {
        unset($this->session->g2apayOrderId);
        unset($this->session->g2apayOrdeToken);
    }

    /**
     * Generate order check session data and return unique token.
     *
     * @param $orderId
     * @return string
     */
    public function generateOrderCheckToken($orderId)
    {
        $token                                  = Random::getAlphanumericString(32);
        $this->session->g2apayOrderCheckId      = $orderId;
        $this->session->g2apayOrderCheckToken   = $token;
        $this->session->g2apayOrderCheckCounter = 10;

        return $token;
    }

    /**
     * Check is order check token is valid.
     *
     * @param $token
     * @return bool
     */
    public function isOrderCheckTokenValid($token)
    {
        return isset($this->session->g2apayOrderCheckToken) && $this->session->g2apayOrderCheckToken === $token;
    }

    /**
     * Check if order check attempt is allowed.
     *
     * @return bool
     */
    public function canCheckOrder()
    {
        $this->session->g2apayOrderCheckCounter = $this->session->g2apayOrderCheckCounter - 1;

        return $this->session->g2apayOrderCheckCounter > 0;
    }

    /**
     * Get order check id.
     *
     * @return mixed
     */
    public function getCheckOrderId()
    {
        return $this->session->g2apayOrderCheckId;
    }
}
