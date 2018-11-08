<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Exceptions\ExchangeRateCreationException;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Котировка.
 *
 * @property int $id
 * @property int $fixed_currency_id
 * @property int $variable_currency_id
 * @property string $rate
 * @property string $date
 *
 * @method static Builder onDate(string $date, int $currencyA, int $currencyB)
 *
 * @package App
 */
class ExchangeRate extends Model
{
    private const USD = 'usd';

    protected $casts = [
        'id' => 'integer',
        'rate' => 'decimal:5',
    ];

    protected $guarded = [];

    /**
     * @return HasOne
     */
    public function fixedCurrency(): HasOne
    {
        return $this->hasOne(Currency::class, 'id', 'fixed_currency_id');
    }

    /**
     * @return HasOne
     */
    public function variableCurrency(): HasOne
    {
        return $this->hasOne(Currency::class, 'id', 'variable_currency_id');
    }

    /**
     * Котировка валют на дату.
     *
     * @param $query
     * @param string $date
     * @param int $fixedCurrencyId
     * @param int $variableCurrencyId
     *
     * @return Builder
     */
    public function scopeOnDate($query, string $date, int $fixedCurrencyId, int $variableCurrencyId): Builder
    {
        return $query->where('date', $date)
            ->where('fixed_currency_id', $fixedCurrencyId)
            ->where('variable_currency_id', $variableCurrencyId);
    }

    /**
     * Создает котировку валют на дату.
     *
     * @param Currency $fixedCurrency
     * @param Currency $variableCurrency
     * @param string $rate
     * @param string $date
     *
     * @throws ExchangeRateCreationException
     *
     * @return ExchangeRate
     */
    public static function create(
        Currency $fixedCurrency,
        Currency $variableCurrency,
        string $rate,
        string $date
    ): ExchangeRate {
        $minRate = config('app.exchange_rate.min');
        if (bccomp($minRate, $rate, 5) === 1) {
            throw new ExchangeRateCreationException("Rates less than $minRate not allowed.");
        }

        if ($variableCurrency->iso !== self::USD) {
            throw new ExchangeRateCreationException('Only USD allowed as variable currency.');
        }

        if ($fixedCurrency->iso === self::USD) {
            throw new ExchangeRateCreationException('USD not allowed as fixed currency.');
        }

        if (Carbon::today()->startOfDay()->gt(Carbon::createFromFormat('Y-m-d', $date))) {
            throw new ExchangeRateCreationException('Date must be today or further.');
        }

        if (static::onDate($date, $fixedCurrency->id, $variableCurrency->id)->exists()) {
            throw new ExchangeRateCreationException('Exchange rate for this date already exists.');
        }

        $exchangeRate = new static();
        $exchangeRate->fixed_currency_id = $fixedCurrency->id;
        $exchangeRate->variable_currency_id = $variableCurrency->id;
        $exchangeRate->rate = $rate;
        $exchangeRate->date = $date;
        $exchangeRate->save();

        return $exchangeRate;
    }
}
