<?php

namespace App;

use Carbon\Carbon;
use App\Events\OperationCreated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Операция.
 *
 * @property int $id
 * @property int $account_id
 * @property string $amount
 * @property string $description описание для отчета (динамически формируемое свойство)
 * @property int $currency_id
 * @property int transaction_id
 * @property Carbon $created_at
 * @property Transaction $transaction
 * @property Account $account
 *
 * @package App
 */
class Operation extends Model
{
    public $dates = ['created_at'];

    protected $casts = [
        'amount' => 'decimal:2',
        'account_id' => 'integer',
        'currency_id' => 'integer',
        'transaction_id' => 'integer',
    ];

    protected $guarded = [];

    /**
     * Принадлежит транзакции.
     *
     * @return BelongsTo
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Принадлежит кошельку.
     *
     * @return BelongsTo
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Формирует свойство description для отображения в отчете.
     *
     * @return string
     */
    public function getDescriptionAttribute(): string
    {
        $description =  $this->transaction->transactionType->name;

        if ($this->transaction->transactionType->id === 2) {
            $description .= " from #{$this->transaction->sender_account_id}";
            $description .= " to #{$this->transaction->receiver_account_id}";
        }

        return $description;
    }

    /**
     * Приход?
     *
     * @return bool
     */
    public function isIncoming(): bool
    {
        return $this->amount > 0;
    }

    /**
     * Создать операцию.
     *
     * @param string $amount
     * @param Transaction $transaction
     * @param Account $account
     *
     * @return Operation
     */
    public static function create(string $amount, Transaction $transaction, Account $account): Operation
    {
        $operation = new static();
        $operation->amount = $amount;
        $operation->currency_id = $account->currency_id;
        $operation->account_id = $account->id;
        $operation->transaction_id = $transaction->id;
        $operation->save();

        event(new OperationCreated($operation));

        return $operation;
    }
}
