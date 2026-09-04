<?php

if (!function_exists('inr')) {
    /**
     * Format a numeric value in Indian Currency format (Lakhs & Crores grouping)
     * using PHP's official built-in \NumberFormatter class.
     * Example: 270995.32 -> 2,70,995.32
     *
     * @param float|int|string|null $amount
     * @param int $decimals
     * @return string
     */
    function inr($amount, int $decimals = 2): string
    {
        $formatter = new \NumberFormatter('en_IN', \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $decimals);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $decimals);

        return $formatter->format((float)($amount ?? 0));
    }
}
