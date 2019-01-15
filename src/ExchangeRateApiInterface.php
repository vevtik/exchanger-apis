<?php
/**
 * @author  Vladimir Dimitrischuck <vevtik@gmail.com>
 */

namespace Exchanger\Api;

/**
 * Interface ExchangeRateApiInterface
 */
interface ExchangeRateApiInterface
{
    /**
     * @param string $basicCurrency
     * @param string $targetCurrency
     *
     * @return bool
     */
    public function support($basicCurrency, $targetCurrency);

    /**
     * @param string $basicCurrency
     * @param string $targetCurrency
     *
     * @return float
     */
    public function getExchangeRate($basicCurrency, $targetCurrency);
}
