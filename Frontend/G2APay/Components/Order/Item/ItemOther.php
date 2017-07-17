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
 * Order item representing any other costs.
 */
class ItemOther implements ItemInterface
{
    const DEFAULT_OTHER_NAME = 'Other';
    const DEFAULT_OTHER_ID   = 'other';
    const DEFAULT_OTHER_TYPE = 'other';

    /**
     * @var float
     */
    protected $amount;

    /**
     * @param float $amount
     */
    public function __construct($amount)
    {
        $this->amount = $amount;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::DEFAULT_OTHER_NAME;
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
        return static::DEFAULT_OTHER_ID;
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
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return static::DEFAULT_OTHER_TYPE;
    }

    /**
     * Get item id.
     *
     * @return string
     */
    public function getId()
    {
        return static::DEFAULT_OTHER_ID;
    }

    /**
     * Get item price.
     *
     * @return float
     */
    public function getPrice()
    {
        return ToolsHelper::roundAmount($this->amount);
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
