<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Кошелек.
 *
 * @property int $id
 * @property int $user_id
 * @property float $balance
 * @property int $currency_id
 * @property Currency $currency
 *
 * @package App
 */
class Account extends Model
{
    public $casts = [
        'balance' => 'decimal:2',
        'user_id' => 'integer',
        'currency_id' => 'integer',
    ];

    protected $guarded = [];

    /**
     * Имеет валюту.
     *
     * @return BelongsTo
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Имеет много операций.
     *
     * @return HasMany
     */
    public function operations(): HasMany
    {
        return $this->hasMany(Operation::class);
    }

    /**
     * Имеет много аггрегированных операций.
     *
     * @return HasMany
     */
    public function operationsAggregations(): HasMany
    {
        return $this->hasMany(OperationsAggregation::class);
    }
}
