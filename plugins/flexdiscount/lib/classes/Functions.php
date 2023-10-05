<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

namespace Igaponov\flexdiscount;

class Functions
{
    /**
     * Common function shop_currency, but with cached variables
     *
     * @param float|int $n
     * @param string|null $in_currency
     * @param string|null $out_currency
     * @param bool $format
     * @return float|int|string
     */
    public function shop_currency($n, $in_currency = null, $out_currency = null, $format = true)
    {
        if (is_array($in_currency)) {
            $options = $in_currency;
            $in_currency = ifset($options, 'in_currency', null);
            $out_currency = ifset($options, 'out_currency', null);
            if (array_key_exists('format', $options)) {
                $format = $options['format']; // can't use ifset because null is a valid value
            } else {
                $format = true;
            }
        }

        $primary = \shopFlexdiscountApp::get('system')['primary_currency'];
        $currency = \shopFlexdiscountApp::get('system')['current_currency'];

        if (!$in_currency) {
            $in_currency = $primary;
        }
        if ($in_currency === true || $in_currency === 1) {
            $in_currency = $currency;
        }
        if (!$out_currency) {
            $out_currency = $currency;
        }

        if ($in_currency != $out_currency) {
            $currencies = \shopFlexdiscountApp::get('system')['config']->getCurrencies(array($in_currency, $out_currency));
            if (isset($currencies[$in_currency]) && $in_currency != $primary) {
                $n = $n * $currencies[$in_currency]['rate'];
            }
            if ($out_currency != $primary) {
                $n = $n / ifempty($currencies[$out_currency]['rate'], 1.0);
            }
        }

        if (($format !== null) && ($info = \waCurrency::getInfo($out_currency)) && isset($info['precision'])) {
            $n = round($n, $info['precision']);
        }

        if ($format === 'h') {
            return wa_currency_html($n, $out_currency);
        } elseif ($format) {
            if (empty($options['extended_format'])) {
                return wa_currency($n, $out_currency);
            } else {
                return \waCurrency::format($options['extended_format'], $n, $currency);
            }
        } else {
            return $this->floatVal($n);
        }
    }

    /**
     * Same as shop_currency, but with ruble sign
     *
     * @param float|int $n
     * @param string|null $in_currency
     * @param string|null $out_currency
     * @param string $format
     * @return float|int|string
     */
    public function shop_currency_html($n, $in_currency = null, $out_currency = null, $format = 'h')
    {
        if (is_array($in_currency)) {
            $in_currency += array(
                'format' => $format,
            );
        }
        return $this->shop_currency($n, $in_currency, $out_currency, $format);
    }

    /**
     * Get float value from string
     *
     * @param string $value
     * @return float
     */
    public function floatVal($value)
    {
        return floatval(str_replace(',', '.', $value));
    }

    /**
     * Create hash from parameters. Uses for saving request values by hash
     *
     * @return string
     */
    public function getRequestHash()
    {
        $args = func_get_args();
        if ($args) {
            $string = '';
            foreach ($args as $arg) {
                if (is_array($arg)) {
                    sort($arg);
                    $string .= json_encode($arg);
                } elseif (is_bool($arg)) {
                    $string .= !!$arg;
                } else {
                    $string .= $arg;
                }
            }
            $hash = md5($string);

            return $hash;
        }
        return '';
    }

    /**
     * Round function
     *
     * @param float $amount
     * @param string $rounding
     * @param string $type - 'discount'|'affiliate'
     * @return float
     */
    public function round($amount, $rounding = '', $type = 'discount')
    {
        static $round = null;
        if ($round === null) {
            $settings = \shopFlexdiscountApp::get('settings');
            $round = array(
                'discount' => ifempty($settings, 'round', 'not'),
                'affiliate' => ifempty($settings, 'affiliate_round', 'not'),
            );
        }
        $rounding = $rounding ? $rounding : $round[$type];
        switch ($rounding) {
            case 'ceil':
                return ceil($amount);
            case 'floor':
                return floor($amount);
            case 'round':
                return round($amount);
            case 'tens':
                return round($amount, -1);
            case 'hund':
                return round($amount, -2);
            case 'dec1':
                return round($amount, 1);
            case 'dec2':
                return round($amount, 2);
            default:
                return $amount;
        }
    }
}