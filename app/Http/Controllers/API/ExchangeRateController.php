<?php

namespace App\Http\Controllers\API;

use App\Currency;
use Carbon\Carbon;
use App\ExchangeRate;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\ExchangeRateResource;
use App\Exceptions\ExchangeRateCreationException;

/**
 * Контроллер загрузки котировки валюты к USD.
 *
 * @package App\Http\Controllers\API
 */
class ExchangeRateController extends Controller
{
    private const USD = 'usd';

    /**
     * @param Request $request
     * @param Currency $fixedCurrency
     *
     * @return ExchangeRateResource
     */
    public function store(Request $request, Currency $fixedCurrency): ?ExchangeRateResource
    {
        if ($fixedCurrency->iso === self::USD) {
            abort(400, 'You can not store usd/usd exchange rate.');
        }

        $today = Carbon::now()->toDateString();
        $minRate = config('app.exchange_rate.min');
        $maxRate = config('app.exchange_rate.max');

        $validatedData = $request->validate([
            'rate' => "required|string|numeric|min:$minRate|max:$maxRate",
            'date' => "required|date_format:Y-m-d|after_or_equal:$today",
        ]);

        try {
            $exchangeRate = ExchangeRate::create(
                $fixedCurrency,
                Currency::whereIso(self::USD)->first(),
                $validatedData['rate'],
                $validatedData['date']
            );

            return new ExchangeRateResource($exchangeRate);
        } catch (ExchangeRateCreationException $exception) {
            abort(400, $exception->getMessage());
        }
    }
}
