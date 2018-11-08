<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Транзакция.
 *
 * @property int $id
 * @property int|null $sender_account_id кошелек-отправитель
 * @property int $receiver_account_id кошелек-получатель
 * @property string $amount
 * @property int $currency_id валюта транзакции
 * @property int $transaction_type_id тип транзакции (пополнение, перевод...)
 * @property TransactionType $transactionType
 * @property Account $account
 *
 * @package App
 */
class Transaction extends Model
{
    public $dates = ['created_at'];

    protected $casts = [
        'amount' => 'decimal:2',
        'currency_id' => 'integer',
        'transaction_type_id' => 'integer',
        'receiver_account_id' => 'integer',
        'sender_account_id' => 'integer',
    ];

    protected $guarded = [];

    /**
     * Принадлежит типу транзакции.
     *
     * @return BelongsTo
     */
    public function transactionType(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class);
    }

    /**
     * Создает транзакцию.
     *
     * @param Account|null $senderAccount
     * @param Account $receiverAccount
     * @param string $amount
     * @param Currency $currency
     * @param TransactionType $transactionType
     *
     * @return Transaction
     */
    public static function create(
        ?Account $senderAccount,
        Account $receiverAccount,
        string $amount,
        Currency $currency,
        TransactionType $transactionType
    ): Transaction {
        $transaction = new static();
        $transaction->transaction_type_id = $transactionType->id;
        $transaction->sender_account_id = $senderAccount !== null ? $senderAccount->id : null;
        $transaction->receiver_account_id = $receiverAccount->id;
        $transaction->amount = $amount;
        $transaction->currency_id = $currency->id;
        $transaction->save();

        return $transaction;
    }
}
