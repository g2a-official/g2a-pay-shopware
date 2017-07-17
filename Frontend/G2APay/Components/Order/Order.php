<?php
/*
 * (c) G2A
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Shopware\G2APay\Components\Order;

use Shopware\Components\Model\ModelEntity;
use Shopware\G2APay\Components\Order\Item\ItemInterface;
use Shopware\G2APay\Components\Order\Item\ItemOther;
use Shopware\G2APay\Components\Order\Item\ItemProduct;
use Shopware\G2APay\Components\Order\Item\ItemShipping;
use Shopware\Models\Country\Country;
use Shopware\Models\Country\State;
use Shopware\Models\Order\Billing;
use Shopware\Models\Order\Shipping;

/**
 * Default order.
 */
class Order implements OrderInterface
{
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
    public function getId()
    {
        return $this->order->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function getAmount()
    {
        return $this->order->getInvoiceAmount();
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrency()
    {
        return $this->order->getCurrency();
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->order->getNumber() . "\n" . $this->order->getTemporaryId();
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomerEmail()
    {
        $customer = $this->order->getCustomer();

        return $customer ? $customer->getEmail() : '';
    }

    /**
     * {@inheritdoc}
     */
    public function getItems()
    {
        $items   = [];
        $details = $this->order->getDetails();
        /** @var \Shopware\Models\Order\Detail $detail */
        foreach ($details as $detail) {
            $item = new ItemProduct($detail);
            if ($item->getAmount() != 0) {
                $items[] = $item;
            }
        }

        $shipping = new ItemShipping($this->order);
        if ($shipping->getAmount() != 0) {
            $items[] = $shipping;
        }

        $itemsAmount = array_reduce($items, function ($current, $item) {
            /* @var $item ItemInterface */
            return $current + $item->getAmount();
        }, 0);

        $totalsDiff = $this->getAmount() - $itemsAmount;
        if (abs($totalsDiff) <= 0.0001) {
            return $items;
        }
        $other = new ItemOther($totalsDiff);
        if ($other->getAmount() != 0) {
            $items[] = $other;
        }

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function setTransactionId($transactionId)
    {
        $this->order->setTransactionId($transactionId);
        $this->save();
    }

    /**
     * {@inheritdoc}
     */
    public function getTransactionId()
    {
        return $this->order->getTransactionId();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrderStatus()
    {
        return $this->order->getOrderStatus();
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentStatus()
    {
        return $this->order->getPaymentStatus();
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentName()
    {
        $payment = $this->order->getPayment();

        return $payment ? $payment->getName() : null;
    }

    /**
     * {@inheritdoc}
     */
    public function complete()
    {
        if (is_null($this->order->getClearedDate())) {
            $this->order->setClearedDate(new \DateTime());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function appendMessage($message)
    {
        $this->order->setInternalComment($this->order->getInternalComment() . "\n" . $message);
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        $manager = Shopware()->Models();
        $manager->persist($this->order);
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function refresh()
    {
        $manager = Shopware()->Models();
        $manager->refresh($this->order);
    }

    /**
     * {@inheritdoc}
     */
    public function getBillingAddress()
    {
        return $this->getAddressArray($this->order->getBilling());
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingAddress()
    {
        return $this->getAddressArray($this->order->getShipping());
    }

    /**
     * Returns array with address params.
     *
     * @param ModelEntity $address
     * @return array
     */
    private function getAddressArray(ModelEntity $address)
    {
        if (!($address instanceof Shipping) || !($address instanceof Billing)) {
            return;
        }

        return [
            'firstname' => $address->getFirstName(),
            'lastname'  => $address->getLastName(),
            'line_1'    => $address->getStreet(),
            'line_2'    => is_null($address->getAdditionalAddressLine1()) ? ''
                : $address->getAdditionalAddressLine1() . ' '
                . $address->getAdditionalAddressLine2(),
            'zip_code' => $address->getZipCode(),
            'company'  => is_null($address->getCompany()) ? '' : $address->getCompany(),
            'city'     => $address->getCity(),
            'county'   => ($address->getState() instanceof State) ? $address->getState()->getName() : null,
            'country'  => ($address->getCountry() instanceof Country) ? $address->getCountry()->getIso() : null,
        ];
    }
}
