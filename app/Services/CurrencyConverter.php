<?php

declare(strict_types=1);

namespace App\Services;

use App\Currency;
use Carbon\Carbon;
use App\ExchangeRate;

/**
 * Конвертер валюты.
 *
 * @package App\Services
 */
class CurrencyConverter
{
    private const USD_ID = 1;

    private const RATE_SCALE = 5;

    /**
     * @param string $amount сумма
     * @param Currency $currency в валюте
     * @param Currency $toCurrency валюта, в которую нужно перевести
     *
     * @return string
     */
    public function convert(string $amount, Currency $currency, Currency $toCurrency): string
    {
        $rate = $this->getRate($currency, $toCurrency);

        return bcmul($rate, $amount, self::RATE_SCALE);
    }

    /**
     * Получить котировку валют на текущий день.
     *
     * @param Currency $fixedCurrency
     * @param Currency $variableCurrency
     *
     * @return string
     */
    private function getRate(Currency $fixedCurrency, Currency $variableCurrency): string
    {
        $dateString = Carbon::now()->toDateString();

        if ($this->isUsd($fixedCurrency) && $this->isUsd($variableCurrency)) {
            return '1';
        }

        if ($this->isUsd($fixedCurrency)) {
            $rate = ExchangeRate::onDate($dateString, $variableCurrency->id, self::USD_ID)
                ->select('rate')
                ->firstOrFail()
                ->rate;

            return bcdiv('1', $rate, self::RATE_SCALE);
        }

        $rateAtoUSD = ExchangeRate::onDate($dateString, $fixedCurrency->id, self::USD_ID)
            ->select('rate')
            ->firstOrFail()
            ->rate;

        if ($this->isUsd($variableCurrency)) {
            return $rateAtoUSD;
        }

        $rateUSDtoB = ExchangeRate::onDate($dateString, $variableCurrency->id, self::USD_ID)
            ->select('rate')
            ->firstOrFail()
            ->rate;

        return bcdiv($rateAtoUSD, $rateUSDtoB, self::RATE_SCALE);
    }

    /**
     * @param Currency $currency
     *
     * @return bool
     */
    private function isUsd(Currency $currency): bool
    {
        return $currency->id === self::USD_ID;
    }
}
