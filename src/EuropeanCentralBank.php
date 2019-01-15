<?php
/**
 * @author  Vladimir Dimitrischuck <vevtik@gmail.com>
 */

namespace Exchanger\Api;

/**
 * Class EuropeanCentralBank
 */
class EuropeanCentralBank implements ExchangeRateApiInterface
{

    const BASIC_CURRENCY = 'EUR';

    /**
     * @var array
     */
    private $rates = [];

    /**
     * @param string $basicCurrency
     * @param string $targetCurrency
     *
     * @return bool
     * @throws \HttpRequestException
     */
    public function support($basicCurrency, $targetCurrency)
    {
        $this->getAllRates();

        return ('EUR' === $basicCurrency || isset($this->rates[$basicCurrency])) &&
            ('EUR' === $targetCurrency || isset($this->rates[$targetCurrency]));
    }

    /**
     * @param string $basicCurrency
     * @param string $targetCurrency
     *
     * @return float
     * @throws \HttpRequestException
     */
    public function getExchangeRate($basicCurrency, $targetCurrency)
    {
        if (! $this->support($basicCurrency, $targetCurrency)) {
            $this->throwNotSupportException($basicCurrency, $targetCurrency);
        }

        if ($basicCurrency === $targetCurrency) {
            return 1;
        } else if (self::BASIC_CURRENCY === $basicCurrency) {
            return $this->rates[$targetCurrency];
        } else if (self::BASIC_CURRENCY === $targetCurrency) {
            if ($this->rates[$basicCurrency]===0) {
                $this->throwNotSupportException($basicCurrency, $targetCurrency);
            }
            return 1 / $this->rates[$basicCurrency];
        } else {
            if ($this->rates[$basicCurrency]===0) {
                $this->throwNotSupportException($basicCurrency, $targetCurrency);
            }
            return $this->rates[$targetCurrency] / $this->rates[$basicCurrency];
        }
    }


    /**
     * @return array
     * @throws \HttpRequestException
     */
    public function getAllRates()
    {
        if (! empty($this->rates)) {
            return $this->rates;
        }

        $rates = [];
        $xml = simplexml_load_string($this->query());
        foreach($xml->Cube->Cube->Cube as $rate) {
            $rates[(string)$rate['currency']] = (string)$rate['rate'];
        }
        unset($xml, $rate);

        $this->rates = $rates;

        return $this->rates;
    }

    /**
     * @return string
     * @throws \HttpRequestException
     */
    private function query()
    {
        $curl = curl_init();
        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => 'http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml',
                CURLOPT_HTTPGET => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT        => 20,
            )
        );
        $result = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        if (empty($result)) {
            throw new \HttpRequestException($error);
        }

        return $result;
    }

    /**
     * @param string $basicCurrency
     * @param string $targetCurrency
     *
     * @throws \InvalidArgumentException
     */
    private function throwNotSupportException($basicCurrency, $targetCurrency)
    {
        throw new \InvalidArgumentException(
            sprintf(
                'EuropeanCentralBankProvider not support exchange %s -> %s',
                $basicCurrency,
                $targetCurrency
            )
        );
    }
}
