<?php
/*
 * (c) G2A
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Shopware\G2APay\Controllers\Frontend;

use Shopware;
use Shopware_Controllers_Frontend_Payment;

/**
 * Abstract module controller base.
 */
abstract class AbstractController extends Shopware_Controllers_Frontend_Payment
{
    protected $translations = [];

    /**
     * @return \Shopware_Plugins_Frontend_G2APay_Bootstrap
     */
    protected function Plugin()
    {
        /* @var  $plugin */
        return $this->get('plugins')->Frontend()->G2APay();
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
        if ($name == 'pluginlogger') {
            $name = 'log';
        }

        return Shopware()->Bootstrap()->getResource(ucfirst($name));
    }

    /**
     * Get simple translation.
     *
     * @param $name
     * @param string $namespace
     * @return mixed
     */
    protected function translate($name, $namespace = '')
    {
        $this->loadTranslations($namespace);

        return $this->translations[$namespace][$name];
    }

    /**
     * Load simple translation.
     *
     * @param $namespace
     */
    protected function loadTranslations($namespace)
    {
        if (isset($this->translations[$namespace])) {
            return;
        }
        $localeDir = $this->Plugin()->getLocaleDir();
        if ($namespace) {
            $localeDir .= $namespace . '/';
        }
        $locale = $this->get('locale')->toString();
        if (!file_exists($localeDir . $locale . '.php')) {
            $locale = 'en_GB';
        }

        $this->translations[$namespace] = include $localeDir . $locale . '.php';
    }
}
