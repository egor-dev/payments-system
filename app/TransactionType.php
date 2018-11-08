<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Тип транзакции.
 *
 * @property int $id
 * @property string $name (пополнение, перевод и т.п.)
 *
 * @package App
 */
class TransactionType extends Model
{
    protected $guarded = [];

    protected $casts = [
        'id' => 'integer',
    ];
}
