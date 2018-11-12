<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Account;
use App\Operation;
use App\Transaction;
use App\TransactionType;
use Illuminate\Bus\Queueable;
use App\Exceptions\TopupException;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Процедура пополнения счета кошелька.
 *
 * @package App\Jobs
 */
class CreateTopup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const RETRY_TRANSACTION_TIMES = 5;

    private const TOPUP_TRANSACTION_TYPE_ID = 1;

    /**
     * @var Account
     */
    private $account;

    /**
     * @var string
     */
    private $amount;

    /**
     * @var TransactionType
     */
    private $transactionType;

    /**
     * CreateTopup constructor.
     *
     * @param Account $account
     * @param string $amount
     *
     * @throws TopupException
     */
    public function __construct(Account $account, string $amount)
    {
        $amountMin = config('app.topup.amount.min');
        $amountMax = config('app.topup.amount.max');
        if ($this->isAmountOutOfAllowedRange($amount, $amountMin, $amountMax)) {
            throw new TopupException("Amount must be between $amountMin and $amountMax.");
        }

        $this->transactionType = TransactionType::findOrFail(self::TOPUP_TRANSACTION_TYPE_ID);
        $this->account = $account;
        $this->amount = $amount;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        DB::transaction(function () {
            $this->account->newQuery()->whereKey($this->account->id)->lockForUpdate()->first();
            $this->account->balance = custom_round(bcadd($this->account->balance, $this->amount));
            $this->account->save();

            $transaction = Transaction::create(
                null,
                $this->account,
                $this->amount,
                $this->account->currency,
                $this->transactionType
            );
            Operation::create($this->amount, $transaction, $this->account);

            return $transaction;
        }, self::RETRY_TRANSACTION_TIMES);
    }

    /**
     * @param string $amount
     * @param string $amountMin
     * @param string $amountMax
     *
     * @return bool
     */
    private function isAmountOutOfAllowedRange(string $amount, $amountMin, $amountMax): bool
    {
        return bccomp($amountMin, $amount) === 1 || bccomp($amount, $amountMax) === 1;
    }
}
