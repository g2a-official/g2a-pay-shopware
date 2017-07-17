<?php
/*
 * (c) G2A
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Shopware\G2APay\Components\Order\Item;

use Shopware\G2APay\Components\Helpers\ToolsHelper;

/**
 * Order item representing shipping.
 */
class ItemShipping implements ItemInterface
{
    const DEFAULT_SHIPPING_NAME = 'Shipping';
    const DEFAULT_SHIPPING_ID   = 'shipping';
    const DEFAULT_SHIPPING_TYPE = 'shipping';

    /**
     * @var \Shopware\Models\Order\Order
     */
    protected $order;

    /**
     * @param \Shopware\Models\Order\Order $order
     */
    public function __construct($order)
    {
        $this->order = $order;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        $dispatch = $this->order->getDispatch();
        $name     = $dispatch ? $dispatch->getName() : self::DEFAULT_SHIPPING_NAME;

        return $name;
    }

    /**
     * {@inheritdoc}
     */
    public function getAmount()
    {
        return $this->getPrice();
    }

    /**
     * {@inheritdoc}
     */
    public function getSku()
    {
        return $this->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function getQuantity()
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtra()
    {
        $dispatch    = $this->order->getDispatch();

        return $dispatch ? $dispatch->getDescription() : '';
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return static::DEFAULT_SHIPPING_TYPE;
    }

    /**
     * Get item id.
     *
     * @return string
     */
    public function getId()
    {
        $dispatch = $this->order->getDispatch();

        return $dispatch ? $dispatch->getId() : static::DEFAULT_SHIPPING_ID;
    }

    /**
     * Get item price.
     *
     * @return float
     */
    public function getPrice()
    {
        return ToolsHelper::roundAmount($this->order->getInvoiceShipping());
    }

    /**
     * Get item url.
     *
     * @return string
     */
    public function getUrl()
    {
        return 'http://' . Shopware()->Config()->Host;
    }
}
