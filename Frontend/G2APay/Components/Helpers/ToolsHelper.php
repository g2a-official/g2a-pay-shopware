<?php
/*
 * (c) G2A
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Shopware\G2APay\Components\Helpers;

/**
 * Utils functions.
 */
abstract class ToolsHelper
{
    /**
     * Returns amount with two decimal places
     *
     * @param $amount
     * @return string
     */
    public static function roundAmount($amount)
    {
        return number_format((float) $amount, 2, '.', '');
    }

    /**
     * Return string hashed with sha256 algorithm
     *
     * @param $string
     * @return string
     */
    public static function hash($string)
    {
        return hash('sha256', $string);
    }
}
