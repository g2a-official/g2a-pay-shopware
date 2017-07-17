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
 * Order item representing product.
 */
class ItemProduct implements ItemInterface
{
    const DEFAULT_PRODUCT_SKU = 'details';

    /**
     * @var \Shopware\Models\Order\Detail
     */
    protected $detail;

    /**
     * @param \Shopware\Models\Order\Detail $detail
     */
    public function __construct($detail)
    {
        $this->detail = $detail;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->detail->getArticleName();
    }

    /**
     * {@inheritdoc}
     */
    public function getAmount()
    {
        return ToolsHelper::roundAmount($this->detail->getPrice() * $this->detail->getQuantity());
    }

    /**
     * {@inheritdoc}
     */
    public function getSku()
    {
        $id = $this->detail->getArticleNumber();
        if (empty($id)) {
            $id = $this->detail->getArticleId();
        }
        if (empty($id)) {
            $id = static::DEFAULT_PRODUCT_SKU;
        }

        return $id;
    }

    /**
     * {@inheritdoc}
     */
    public function getQuantity()
    {
        return $this->detail->getQuantity();
    }

    /**
     * {@inheritdoc}
     */
    public function getExtra()
    {
        return $this->detail->getArticleNumber();
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'product';
    }

    /**
     * Get item id.
     *
     * @return string
     */
    public function getId()
    {
        return $this->detail->getArticleId() === 0 ? 1 : $this->detail->getArticleId();
    }

    /**
     * Get item price.
     *
     * @return float
     */
    public function getPrice()
    {
        return ToolsHelper::roundAmount($this->detail->getPrice());
    }

    /**
     * Get item url.
     *
     * @return string
     */
    public function getUrl()
    {
        if ($this->detail->getArticleId() === 0) {
            return 'http://' . Shopware()->Config()->Host;
        }

        return Shopware()->Front()->Router()->assemble([
            'controller' => 'detail',
            'sArticle'   => $this->getId(),
            'title'      => $this->getName(),
        ]);
    }
}
