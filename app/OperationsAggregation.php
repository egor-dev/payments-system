<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Дневная аггрегация операций кошелька.
 *
 * @property int $account_id
 * @property string $sum сумма в валюте счета
 * @property string $sum_usd сумма в USD
 * @property string $date дата
 *
 * @package App
 */
class OperationsAggregation extends Model
{
    protected $casts = [
        'id' => 'integer',
        'sum' => 'decimal:2',
        'sum_usd' => 'decimal:2',
    ];

    protected $guarded = [];

    /**
     * Создать аггрегацию.
     *
     * @param $attributes
     *
     * @return OperationsAggregation
     */
    public static function create($attributes): OperationsAggregation
    {
        $operationsAggregation = new static();
        $operationsAggregation->fill($attributes);
        $operationsAggregation->save();

        return $operationsAggregation;
    }
}
