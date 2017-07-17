<?php
/*
 * (c) G2A
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Shopware\G2APay\Components\Order\Item;

/**
 * Order Item Interface.
 */
interface ItemInterface
{
    /**
     * Get item SKU.
     *
     * @return mixed
     */
    public function getSku();

    /**
     * Get item name.
     *
     * @return string
     */
    public function getName();

    /**
     * Get item amount.
     *
     * @return float
     */
    public function getAmount();

    /**
     * Get item quantity.
     *
     * @return int
     */
    public function getQuantity();

    /**
     * Get item extra description.
     *
     * @return string|null
     */
    public function getExtra();

    /**
     * Get item type.
     *
     * @return string
     */
    public function getType();

    /**
     * Get item id.
     *
     * @return string
     */
    public function getId();

    /**
     * Get item price.
     *
     * @return float
     */
    public function getPrice();

    /**
     * Get item url.
     *
     * @return string
     */
    public function getUrl();
}
