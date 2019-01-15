<?php
/**
 * @author  Vladimir Dimitrischuck <vevtik@gmail.com>
 */

namespace Exchanger\Api;

/**
 * Class FixRate
 */
class FixRate implements ExchangeRateApiInterface
{
    private $supportPairs = [];

    public function support($basicCurrency, $targetCurrency)
    {
        return $this->keyExists($basicCurrency, $targetCurrency);
    }

    public function getExchangeRate($basicCurrency, $targetCurrency)
    {
        if (! $this->support($basicCurrency, $targetCurrency)) {
            throw new \LogicException('This currencies does not supports.');
        }

        return $this->supportPairs[$this->getKey($basicCurrency, $targetCurrency)];
    }

    /**
     * @param string $basicCurrency
     * @param string $targetCurrency
     *
     * @return bool
     */
    public function keyExists($basicCurrency, $targetCurrency)
    {
        return isset($this->supportPairs[$this->getKey($basicCurrency, $targetCurrency)]);
    }

    /**
     * @param string $basicCurrency
     * @param string $targetCurrency
     *
     * @return string
     */
    public function getKey($basicCurrency, $targetCurrency)
    {
        return sprintf('%s_%s', $basicCurrency, $targetCurrency);
    }

    /**
     * @return array
     */
    public function getSupportPairs()
    {
        return $this->supportPairs;
    }

    /**
     * @param array $supportPairs
     *
     * @return $this
     */
    public function setSupportPairs($supportPairs)
    {
        $this->supportPairs = $supportPairs;

        return $this;
    }

    /**
     * @param string $pair
     * @param float $rate
     *
     * @return $this
     */
    public function addSupportPair($pair, $rate)
    {
        $this->supportPairs[$pair] = $rate;

        return $this;
    }

}
