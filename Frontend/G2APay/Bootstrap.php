<?php
/*
 * (c) G2A
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/Components/Autoload.php';
spl_autoload_register([\Shopware\G2APay\Components\Autoload::instance(), 'load']);

use Shopware\G2APay\Components\Helpers\ConfigHelper;
use Shopware\G2APay\Components\Helpers\OrderHelper;
use Shopware\G2APay\Components\Helpers\TransactionHelper;
use Shopware\Models\Property\Option;
use Shopware\Models\Property\Value;
use Shopware\Models\Property\Group;

/**
 * G2A Pay Module.
 */
class Shopware_Plugins_Frontend_G2APay_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    protected $configHelper;
    protected $orderHelper;
    protected $transactionHelper;

    /**
     * Constructor method.
     *
     * @param                     $name
     * @param Enlight_Config|null $info
     */
    public function __construct($name, $info = null)
    {
        parent::__construct($name, $info);
    }

    /**
     * After init.
     */
    public function afterInit()
    {
        parent::afterInit();
        $this->configHelper      = new ConfigHelper($this->Config());
        $this->orderHelper       = new OrderHelper($this->Config());
        $this->transactionHelper = new TransactionHelper();
    }

    /**
     * ConfigHelper instance.
     *
     * @return ConfigHelper
     */
    public function ConfigHelper()
    {
        return $this->configHelper;
    }

    /**
     * OrderHelper instance.
     *
     * @return OrderHelper
     */
    public function OrderHelper()
    {
        return $this->orderHelper;
    }

    /**
     * TransactionHelper instance.
     *
     * @return TransactionHelper
     */
    public function TransactionHelper()
    {
        return $this->transactionHelper;
    }

    /**
     * Install module.
     *
     * @return bool
     */
    public function install()
    {
        try {
            $this->addEvents();
            $this->addBackendEvents();
            $this->addPayment();
            $this->addTable();
            $this->addForm();
            $this->createSubscriptionAttribute();
            $this->alterTableWithIsSubscription();
        } catch (\Exception $e) {
            if ($e->getCode() === 42) {
                return true;
            }

            return ['message' => $e->getMessage()];
        }

        return true;
    }

    /**
     * Subscribe to events.
     */
    protected function addEvents()
    {
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_PaymentG2APay',
            'onGetControllerPathPayment'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_IpnG2APay',
            'onGetControllerPathIpn'
        );
    }

    /**
     * Subscribe to backend events.
     */
    protected function addBackendEvents()
    {
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_TransactionG2APay',
            'onGetControllerPathBackendTransaction'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch_Backend_Order',
            'extendOrderDetailView'
        );
    }

    protected function addTable()
    {
        Shopware()->Db()->query(
            "CREATE TABLE IF NOT EXISTS `s_g2apay_transaction` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `order_id` int(11) unsigned NOT NULL DEFAULT '0',
              `transaction_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              `status` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
              `refunded_amount` double NOT NULL DEFAULT '0',
              `create_time` datetime NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
        ");
    }

    /**
     * Alter table with isSubscription column. Used since version 0.2.2.
     */
    protected function alterTableWithIsSubscription()
    {
        Shopware()->Db()->query('ALTER TABLE `s_g2apay_transaction` 
                ADD `subscription_id` varchar(255) DEFAULT NULL;
                ALTER TABLE `s_g2apay_transaction` 
                ADD `is_subscription` BOOLEAN NOT NULL DEFAULT FALSE;');
    }

    /**
     * Creates subscription product attribute.
     *
     * @return bool
     */
    protected function createSubscriptionAttribute()
    {
        try {
            $option = new Option();
            $option->fromArray([
            'id'         => 0,
            'name'       => 'G2A Pay Subscription',
            'filterable' => true,
            ]);

            Shopware()->Models()->persist($option);
            Shopware()->Models()->flush();

            $value = new Value($option, OrderHelper::MONTHLY_SUBSCRIPTION_NAME);

            Shopware()->Models()->persist($value);
            Shopware()->Models()->flush();

            $group = new Group();
            $group->fromArray([
                'id'         => 0,
                'position'   => 0,
                'name'       => 'G2A Pay',
                'comparable' => true,
                'isOption'   => false,
                'sortMode'   => 0,
            ]);

            $group->addOption($option);

            Shopware()->Models()->persist($group);
            Shopware()->Models()->flush();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Add config form.
     */
    protected function addForm()
    {
        $form = $this->Form();

        $form->setElement('text', 'g2apayApiHash', [
            'label'        => 'API Hash',
            'required'     => true,
            'scope'        => \Shopware\Models\Config\Element::SCOPE_SHOP,
            'stripCharsRe' => ' ',
        ]);

        $form->setElement('text', 'g2apayApiSecret', [
            'label'        => 'API Secret',
            'required'     => true,
            'scope'        => \Shopware\Models\Config\Element::SCOPE_SHOP,
            'stripCharsRe' => ' ',
        ]);

        $form->setElement('text', 'g2apayMerchantEmail', [
            'label'        => 'Merchant Email',
            'required'     => true,
            'scope'        => \Shopware\Models\Config\Element::SCOPE_SHOP,
            'stripCharsRe' => ' ',
        ]);

        $form->setElement('select', 'g2apayEnvironment', [
            'label' => 'Environment',
            'value' => ConfigHelper::DEFAULT_ENVIRONMENT,
            'store' => array_map(function ($value) {
                return [$value, ucwords($value)];
            }, ConfigHelper::getEnvironments()),
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
        ]);

        $form->setElement('select', 'g2apayPaymentStatusId', [
            'label'        => 'Payment status after receiving payment',
            'value'        => 12,
            'store'        => 'base.PaymentStatus',
            'displayField' => 'description',
            'valueField'   => 'id',
            'scope'        => \Shopware\Models\Config\Element::SCOPE_SHOP,
        ]);

        $form->setElement('select', 'g2apayPendingStatusId', [
            'label'        => 'Order status after receiving payment',
            'value'        => 1,
            'store'        => 'base.OrderStatus',
            'displayField' => 'description',
            'valueField'   => 'id',
            'scope'        => \Shopware\Models\Config\Element::SCOPE_SHOP,
        ]);

        $form->setElement('select', 'g2apayCancelledStatusId', [
            'label'        => 'Cancelled order status',
            'value'        => 4,
            'store'        => 'base.OrderStatus',
            'displayField' => 'description',
            'valueField'   => 'id',
            'scope'        => \Shopware\Models\Config\Element::SCOPE_SHOP,
        ]);

        $form->setElement('text', 'g2apayIpnSecret', [
            'label'        => 'IPN secret',
            'required'     => true,
            'scope'        => \Shopware\Models\Config\Element::SCOPE_SHOP,
            'stripCharsRe' => ' ',
        ]);
    }

    /**
     * Get Gateway controller path.
     *
     * @param Enlight_Event_EventArgs $args
     * @return string
     */
    public function onGetControllerPathPayment(Enlight_Event_EventArgs $args)
    {
        $this->get('template')->addTemplateDir($this->Path() . 'Views/', 'g2apay');

        return $this->Path() . 'Controllers/Frontend/PaymentG2APay.php';
    }

    /**
     * Get IPN controller path.
     *
     * @param Enlight_Event_EventArgs $args
     * @return string
     */
    public function onGetControllerPathIpn(Enlight_Event_EventArgs $args)
    {
        return $this->Path() . 'Controllers/Frontend/IpnG2APay.php';
    }

    /**
     * Get backend controller path.
     *
     * @param Enlight_Event_EventArgs $args
     * @return string
     */
    public function onGetControllerPathBackendTransaction(Enlight_Event_EventArgs $args)
    {
        Shopware()->Template()->addTemplateDir($this->Path() . 'Views/');

        return $this->Path() . 'Controllers/Backend/TransactionG2APay.php';
    }

    /**
     * Extend backend order detail view.
     *
     * @param $arguments
     */
    public function extendOrderDetailView($arguments)
    {
        $view       = $arguments->getSubject()->View();
        $actionName = $arguments->getRequest()->getActionName();

        $view->addTemplateDir($this->Path() . 'Views/');

        if ($actionName === 'load') {
            $view->extendsTemplate('backend/transaction_g2apay/view/detail/window.js');
        } elseif ($actionName === 'index') {
            $view->extendsTemplate('backend/transaction_g2apay/app.js');
        }
    }

    /**
     * Get simple translation locale dir.
     *
     * @return string
     */
    public function getLocaleDir()
    {
        return $this->Path() . 'Views/frontend/_resources/locale/';
    }

    /**
     * Create G2A Payment.
     */
    protected function addPayment()
    {
        $this->createPayment([
            'name'                  => 'g2apay',
            'description'           => 'G2A Pay',
            'action'                => 'payment_g2apay',
            'active'                => 1,
            'position'              => 1,
            'additionalDescription' => '<!-- paymentLogo -->
			<div id="payment_desc"><img src="data:image/png;base64,' .
                base64_encode(file_get_contents(dirname(__FILE__) . '/img/logo.png')) . '" alt="G2A Pay" />
			<!-- paymentLogo --></div>',
        ]);
    }

    /**
     * Uninstall module.
     *
     * @return bool
     */
    public function uninstall()
    {
        return true;
    }

    /**
     * Update module.
     *
     * @param string $oldVersion
     * @return bool
     */
    public function update($oldVersion)
    {
        if (version_compare($oldVersion, '0.2.0', '<')) {
            $this->addBackendEvents();
            $this->addTable();
        }

        if (version_compare($oldVersion, '0.2.2', '<')) {
            $this->createSubscriptionAttribute();
            $this->alterTableWithIsSubscription();
        }

        return true;
    }

    /**
     * Get payment instance.
     *
     * @return null|object
     */
    public function Payment()
    {
        return $this->Payments()->findOneBy(
            ['name' => 'g2apay']
        );
    }

    /**
     * Enable payment module.
     *
     * @return array
     */
    public function enable()
    {
        $this->updatePaymentActive(true);

        return [
            'success'         => true,
            'invalidateCache' => ['config', 'backend', 'proxy', 'frontend'],
        ];
    }

    /**
     * Disable payment module.
     *
     * @return array
     */
    public function disable()
    {
        $this->updatePaymentActive(false);

        return [
            'success'         => true,
            'invalidateCache' => ['config', 'backend'],
        ];
    }

    /**
     * Update payment module state.
     *
     * @param $active
     */
    protected function updatePaymentActive($active)
    {
        $payment = $this->Payment();
        if ($payment !== null) {
            $payment->setActive($active);
            $this->get('models')->flush($payment);
        }
    }

    /**
     * Get module info.
     *
     * @return array
     */
    public function getInfo()
    {
        $router = Shopware()->Front()->Router();
        $ipnUrl = $router->assemble([
            'controller'  => 'ipn_g2apay',
            'action'      => 'process',
            'module'      => 'frontend',
            'forceSecure' => 1,
            'secret'      => 'xxxxx',
        ]);

        return [
            'version'     => $this->getVersion(),
            'author'      => 'G2A Team',
            'label'       => 'G2A Pay',
            'description' => '<p><img src="data:image/png;base64,' .
                base64_encode(file_get_contents(dirname(__FILE__) . '/img/logo.png'))
                . '" /></p><p></p><p>G2A Pay payment method</p>'
                . '<p></p><p>IPN Secret URL: ' . $ipnUrl . ' (replace "xxxxx" with your IPN secret)</p>',
            'copyright' => 'Copyright © 2015, G2A.COM',
            'support'   => 'G2A.COM',
            'link'      => 'https://pay.g2a.com/',
        ];
    }

    /**
     * Get module version.
     *
     * @return string
     */
    public function getVersion()
    {
        return '0.2.2';
    }
}
