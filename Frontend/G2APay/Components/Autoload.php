<?php
/*
 * (c) G2A
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Shopware\G2APay\Components;

/**
 * Module autoload class.
 */
class Autoload
{
    /**
     * @var Autoload
     */
    protected static $instance;

    /**
     * Get autoload instance.
     *
     * @return Autoload
     */
    public static function instance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    /**
     * Autoload class.
     *
     * @param $class
     */
    public function load($class)
    {
        $prefix = 'Shopware\G2APay';

        if (strpos($class, $prefix) !== 0) {
            return;
        }
        $path     = str_replace('\\', '/', substr($class, strlen($prefix)));
        $fullPath = realpath(dirname(__FILE__) . '/..' . $path . '.php');
        if ($fullPath) {
            require_once $fullPath;
        }
    }
}
