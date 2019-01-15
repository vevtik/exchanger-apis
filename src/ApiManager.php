<?php
/**
 * @author  Vladimir Dimitrischuck <vevtik@gmail.com>
 */

namespace Exchanger\Api;

/**
 * Class ApiManager
 */
class ApiManager implements ExchangeRateApiInterface
{
    /**
     * @var array
     */
    private $supportPairs = [];


    /**
     * @param string $basicCurrency
     * @param string $targetCurrency
     *
     * @return bool
     */
    public function support($basicCurrency, $targetCurrency)
    {
        $pair = $this->getKey($basicCurrency, $targetCurrency);

        return $basicCurrency === $targetCurrency || (! is_null($this->getDirectionConfid($pair)));
    }

    public function getDirectionConfid($pair)
    {
        if (isset($this->supportPairs[$pair])) {
            return $this->supportPairs[$pair];
        }
        foreach ($this->supportPairs as $key=>$value) {
            if ($this->isRegexpPair($key) && preg_match($key, $pair)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param string $pair
     *
     * @return bool
     */
    private function isRegexpPair($pair)
    {
        return '#' === $pair[0];
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
     * @param string $key
     *
     * @return array
     */
    public function reverseKey($key) {
        $array = explode('_', $key);
        if (2!==count($array)) {
            throw new \LogicException();
        }

        return [
            'basicCurrency' => $array[0],
            'targetCurrency' => $array[1]
        ];
    }

    /**
     * @param string $basicCurrency
     * @param string $targetCurrency
     *
     * @return float
     * @throws \Exception
     */
    public function getExchangeRate($basicCurrency, $targetCurrency)
    {
        if ($basicCurrency === $targetCurrency) {
            return 1;
        }
        if (! $this->support($basicCurrency, $targetCurrency)) {
            throw new \LogicException(sprintf('Unable to get exchange rate for %s', $this->getKey($basicCurrency, $targetCurrency)));
        }

        return $this->_getExchangeRate($basicCurrency, $targetCurrency);
    }

    /**
     * @param string $basicCurrency
     * @param string $targetCurrency
     *
     * @return float
     * @throws \Exception
     */
    protected function _getExchangeRate($basicCurrency, $targetCurrency)
    {
        $pair = $this->getKey($basicCurrency, $targetCurrency);

        $directionConfig = $this->getDirectionConfid($pair);

        foreach ($directionConfig as $api) {
            try {
                if ($api instanceof ExchangeRateApiInterface) {
                    $result = $api->getExchangeRate($basicCurrency, $targetCurrency);
                } else if (is_array($api)) {
                    $result = $this->_getMultipleExchangeRate($basicCurrency, $targetCurrency, $api);
                } else {
                    throw new \LogicException('Configuration Error');
                }


                return $result;
            } catch (\Exception $e) {
                //Report to support
            }
        }

        throw new \InvalidArgumentException(sprintf("Unable to get exchange rate for %s", $this->getKey($basicCurrency, $targetCurrency)));
    }

    /**
     * @param string $basicCurrency
     * @param string $targetCurrency
     * @param array  $targets
     *
     * @return float
     * @throws \Exception
     */
    protected function _getMultipleExchangeRate($basicCurrency, $targetCurrency, $targets)
    {
        $result = 1;
        foreach ($targets as $currency) {
            $next = str_replace('{target}', $targetCurrency, $currency);
            $result *= $this->_getExchangeRate($basicCurrency, $next);
            $basicCurrency = $next;
        }

        return $result;
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
     * @param array  $params
     *
     * @return $this
     */
    public function addSupportPair($pair, array $params)
    {
        if (! $this->checkPairParams($params)) {
            throw new \LogicException();
        }
        $this->supportPairs[$pair] = $params;

        return $this;
    }

    /**
     * @param array $params
     *
     * @return bool
     */
    private function checkPairParams(array $params)
    {
        foreach ($params as $item) {
            if (! ($item instanceof ExchangeRateApiInterface || is_array($item))) {
                return false;
            }
        }

        return true;
    }
}
