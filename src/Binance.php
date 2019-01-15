<?php
/**
 * @author  Vladimir Dimitrischuck <vevtik@gmail.com>
 */
namespace Exchanger\Api;

/**
 * Class Binance
 */
class Binance implements ExchangeRateApiInterface
{
    const PUBLIC_URL = 'https://www.binance.com/api/v3/ticker/price';
    const FIELD_SYMBOL = 'symbol';
    const FIELD_PRICE = 'price';

    /**
     * @var array
     */
    private  $supportPairs = [
        'BTCUSDT', 'ETHUSDT', 'LTCUSDT', 'BCCUSDT', 'LTCUSDT'
    ];

    /**
     * @var array
     */
    private $rates = array();

    /**
     * @var bool
     */
    private $supportAllRates = false;

    public function support($basicCurrency, $targetCurrency)
    {
        return $this->keyExists($basicCurrency, $targetCurrency) ||
        $this->keyExists($targetCurrency, $basicCurrency);
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
        if (! $this->support($basicCurrency, $targetCurrency)) {
            throw new \LogicException('Binance does not support this currencies.');
        }

        if (! $this->supportAllRates) {
            return $this->getSimpleRate($basicCurrency, $targetCurrency);
        }

        if (empty($this->rates)) {
            $this->getAllRates();
        }

        if (in_array($this->getKey($targetCurrency, $basicCurrency), $this->rates)) {
            return 1/$this->rates[$this->getKey($targetCurrency, $basicCurrency)];
        } else if (in_array($this->getKey($basicCurrency, $targetCurrency), $this->rates)) {
            return $this->rates[$this->getKey($basicCurrency, $targetCurrency)];
        } else {
            throw new \InvalidArgumentException('Binance does not support this currencies.');
        }
    }

    /**
     * @param $basicCurrency
     * @param $targetCurrency
     *
     * @return float
     * @throws \Exception
     */
    private function getSimpleRate($basicCurrency, $targetCurrency)
    {
        $targetKey = $this->getKey($basicCurrency, $targetCurrency);
        $ourKey = $this->keyExists($basicCurrency, $targetCurrency) ? $this->getKey($basicCurrency, $targetCurrency) :
            $this->getKey($targetCurrency, $basicCurrency);
        if (in_array($ourKey, $this->rates)) {
            return $targetKey !== $ourKey ? 1/$this->rates[$ourKey] : $this->rates[$ourKey];
        }

        $rate = $this->query($ourKey);
        if (
            ! (
                is_array($rate) &&
                isset($rate[self::FIELD_SYMBOL]) &&
                isset($rate[self::FIELD_PRICE]) &&
                $ourKey === $rate[self::FIELD_SYMBOL] &&
                $rate[self::FIELD_PRICE] > 0
            )
        ) {
            throw new \InvalidArgumentException("Binance: Undefined response");
        }
        $this->rates[$ourKey] = $rate[self::FIELD_PRICE];

        return $targetKey !== $ourKey ? 1/$this->rates[$ourKey] : $this->rates[$ourKey];
    }

    /**
     * @param string $basicCurrency
     * @param string $targetCurrency
     *
     * @return bool
     */
    public function keyExists($basicCurrency, $targetCurrency)
    {
        return in_array($this->getKey($basicCurrency, $targetCurrency), $this->supportPairs);
    }

    /**
     * @param string $basicCurrency
     * @param string $targetCurrency
     *
     * @return string
     */
    public function getKey($basicCurrency, $targetCurrency)
    {
        return sprintf('%s%s', $this->getCurrencyAlias($basicCurrency), $this->getCurrencyAlias($targetCurrency));
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getAllRates() {
        $queryResult = $this->query();
        $this->rates = array();

        foreach ($queryResult as $item) {
            if (in_array($item[self::FIELD_SYMBOL], $this->supportPairs)) {
                $this->rates[$item[self::FIELD_SYMBOL]] = $item[self::FIELD_PRICE];
            }
        }
        if (empty($this->rates)) {
            throw new \InvalidArgumentException("Binance: Undefined response");
        }

        return $this->rates;
    }

    /**
     * @param string $pair
     *
     * @return array
     * @throws \Exception
     */
    private function query($pair = null)
    {
        $curl = curl_init();
        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => self::PUBLIC_URL . (empty($pair) ? '': '?' . http_build_query(['symbol'=>$pair])),
                CURLOPT_HTTPGET => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT        => 20,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_SSL_VERIFYHOST => 0
            )
        );
        $result = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        if (empty($result)) {
            throw new \Exception($error);
        }
        $json = json_decode($result, true);

        return $json;
    }

    private function getCurrencyAlias($currency)
    {
        return $currency === 'BCH' ? 'BCC' : $currency;
    }
}
