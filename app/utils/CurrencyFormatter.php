<?php

namespace Akti\Utils;

/**
 * CurrencyFormatter — Format monetary values for different locales/currencies.
 * FEAT-009: Multi-currency support
 */
class CurrencyFormatter
{
    private static array $currencies = [
        'BRL' => ['symbol' => 'R$', 'decimal' => ',', 'thousands' => '.', 'precision' => 2],
        'USD' => ['symbol' => '$',  'decimal' => '.', 'thousands' => ',', 'precision' => 2],
        'EUR' => ['symbol' => '€',  'decimal' => ',', 'thousands' => '.', 'precision' => 2],
        'GBP' => ['symbol' => '£',  'decimal' => '.', 'thousands' => ',', 'precision' => 2],
    ];

    /**
     * Format a value in the given currency.
     *
     * @param  float|int|string $value
     * @param  string           $currency ISO code (BRL, USD, EUR)
     * @param  bool             $showSymbol
     * @return string
     */
    public static function format($value, string $currency = 'BRL', bool $showSymbol = true): string
    {
        $value = (float) $value;
        $config = self::$currencies[$currency] ?? self::$currencies['BRL'];

        $formatted = number_format(
            $value,
            $config['precision'],
            $config['decimal'],
            $config['thousands']
        );

        return $showSymbol ? $config['symbol'] . ' ' . $formatted : $formatted;
    }

    /**
     * Parse a locale-formatted string back to a float.
     *
     * @param  string $value
     * @param  string $currency
     * @return float
     */
    public static function parse(string $value, string $currency = 'BRL'): float
    {
        $config = self::$currencies[$currency] ?? self::$currencies['BRL'];
        $value = str_replace($config['symbol'], '', $value);
        $value = trim($value);
        $value = str_replace($config['thousands'], '', $value);
        $value = str_replace($config['decimal'], '.', $value);

        return (float) $value;
    }

    /**
     * Get available currencies list.
     *
     * @return array
     */
    public static function getAvailable(): array
    {
        return array_keys(self::$currencies);
    }
}
