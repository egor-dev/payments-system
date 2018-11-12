<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Currency;
use Carbon\Carbon;
use App\OperationsAggregation;
use App\Events\OperationCreated;
use App\Services\CurrencyConverter;

/**
 * Создает или обновляет аггрегацию по операциям кошелька на день.
 *
 * @package App\Listeners
 */
class InsertOrUpdateOperationsAggregation
{
    /**
     * Create the event listener.
     *
     * @param CurrencyConverter $currencyConverter
     */
    private const USD = 'usd';

    /**
     * @var CurrencyConverter
     */
    private $currencyConverter;

    public function __construct(CurrencyConverter $currencyConverter)
    {
        $this->currencyConverter = $currencyConverter;
    }

    /**
     * Handle the event.
     *
     * @param OperationCreated $event
     *
     * @return void
     */
    public function handle(OperationCreated $event)
    {
        $operation = $event->operation;
        $accountCurrency = $operation->account->currency;
        $usdCurrency = Currency::whereIso(self::USD)->firstOrFail();

        $amount = (string) abs($operation->amount);
        $amountUsd = $this->currencyConverter->convert($amount, $accountCurrency, $usdCurrency);

        $date = Carbon::now()->toDateString();

        $operationsAggregation = OperationsAggregation::query()
            ->where('account_id', $operation->account_id)
            ->select('sum', 'sum_usd', 'id')
            ->where('date', $date)
            ->first();

        if (null === $operationsAggregation) {
            OperationsAggregation::create([
                'sum' => $amount,
                'sum_usd' => $amountUsd,
                'date' => $date,
                'account_id' => $operation->account_id,
            ]);
        } else {
            /** @var $operationsAggregation OperationsAggregation */
            $operationsAggregation->sum = bcadd($operationsAggregation->sum, $amount);
            $operationsAggregation->sum_usd = bcadd($operationsAggregation->sum_usd, $amountUsd);
            $operationsAggregation->save();
        }
    }
}
