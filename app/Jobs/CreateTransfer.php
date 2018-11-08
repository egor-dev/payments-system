<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Account;
use App\Currency;
use App\Operation;
use Carbon\Carbon;
use App\Transaction;
use App\TransactionType;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use App\Services\CurrencyConverter;
use App\Exceptions\TransferException;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Процедура перевода средств с кошелька на кошелек.
 *
 * @package App\Jobs
 */
class CreateTransfer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const TRANSFER_TRANSACTION_TYPE_ID = 2;

    private const RETRY_TRANSACTION_TIMES = 5;

    /**
     * @var string
     */
    private $amount;
    /**
     * @var Account
     */
    private $senderAccount;
    /**
     * @var Account
     */
    private $receiverAccount;
    /**
     * @var Currency
     */
    private $transferCurrency;

    /**
     * @var TransactionType
     */
    private $transactionType;

    /**
     * @var string
     */
    private $amountMin;

    /**
     * @var string
     */
    private $amountMax;

    /**
     * Create a new job instance.
     *
     * @param string $amount
     * @param Account $senderAccount
     * @param Account $receiverAccount
     * @param Currency $currency
     *
     * @throws TransferException
     */
    public function __construct(string $amount, Account $senderAccount, Account $receiverAccount, Currency $currency)
    {
        $this->amountMin = config('app.transfer.amount.min');
        $this->amountMax = config('app.transfer.amount.max');

        if ($this->isOutOfAllowedRange($amount)) {
            throw new TransferException('Amount is out of allowed range.');
        }

        if ($this->isSameAccount($senderAccount, $receiverAccount)) {
            throw new TransferException('Can not transfer to same account.');
        }

        if ($this->isTransferCurrencyDifferentToBothAccounts($currency, $senderAccount, $receiverAccount)) {
            throw new TransferException('Transfer currency does not match any account currencies.');
        }

        $this->amount = $amount;
        $this->senderAccount = $senderAccount;
        $this->receiverAccount = $receiverAccount;
        $this->transferCurrency = $currency;
        $this->transactionType = TransactionType::findOrFail(self::TRANSFER_TRANSACTION_TYPE_ID);
    }

    /**
     * Execute the job.
     *
     * @param CurrencyConverter $currencyConverter
     *
     * @throws TransferException
     *
     * @return void
     */
    public function handle(CurrencyConverter $currencyConverter)
    {
        $subtractAmount = $this->amount;
        $addAmount = $this->amount;

        if ($this->senderAccount->currency_id !== $this->transferCurrency->id) {
            $subtractAmount = $currencyConverter->convert(
                $this->amount,
                $this->transferCurrency,
                $this->senderAccount->currency
            );
        }

        if ($this->receiverAccount->currency_id !== $this->transferCurrency->id) {
            $addAmount = $currencyConverter->convert(
                $this->amount,
                $this->transferCurrency,
                $this->receiverAccount->currency
            );
        }

        if ($this->isOutOfAllowedRange($addAmount)) {
            throw new TransferException("Add amount $addAmount is out of allowed range.");
        }

        if ($this->isOutOfAllowedRange($subtractAmount)) {
            throw new TransferException("Subtract amount $subtractAmount is out of allowed range.");
        }

        DB::transaction(function () use ($addAmount, $subtractAmount) {
            $this->senderAccount->newQuery()->whereKey($this->senderAccount->id)->lockForUpdate()->first();
            $this->receiverAccount->newQuery()->whereKey($this->receiverAccount->id)->lockForUpdate()->first();

            if (bccomp($subtractAmount, $this->senderAccount->balance) === 1) {
                throw new TransferException('Not enough money on sender account.');
            }

            $times = Transaction::where('sender_account_id', $this->senderAccount->id)
                ->where('transaction_type_id', self::TRANSFER_TRANSACTION_TYPE_ID)
                ->where('created_at', '>=', Carbon::now()->subSeconds(config('app.transfer.frequency.period_seconds')))
                ->count();

            if ($times >= config('app.transfer.frequency.times_in_period')) {
                throw new TransferException('Can not transfer too frequently.');
            }

            $this->senderAccount->balance = bcsub($this->senderAccount->balance, $subtractAmount);
            $this->senderAccount->save();
            $this->receiverAccount->balance = bcadd($this->receiverAccount->balance, $addAmount);
            $this->receiverAccount->save();

            $transaction = Transaction::create(
                $this->senderAccount,
                $this->receiverAccount,
                $this->amount,
                $this->transferCurrency,
                $this->transactionType
            );
            Operation::create("-$subtractAmount", $transaction, $this->senderAccount);
            Operation::create($addAmount, $transaction, $this->receiverAccount);

            return $transaction;
        }, self::RETRY_TRANSACTION_TIMES);
    }

    /**
     * @param Currency $transferCurrency
     * @param Account $senderAccount
     * @param Account $receiverAccount
     *
     * @return bool
     */
    private function isTransferCurrencyDifferentToBothAccounts(
        Currency $transferCurrency,
        Account $senderAccount,
        Account $receiverAccount
    ): bool {
        return !\in_array($transferCurrency->id, [$senderAccount->currency_id, $receiverAccount->currency_id], true);
    }

    /**
     * @param Account $accountA
     * @param Account $accountB
     *
     * @return bool
     */
    private function isSameAccount(Account $accountA, Account $accountB): bool
    {
        return $accountA->id === $accountB->id;
    }

    /**
     * @param string $amount
     *
     * @return bool
     */
    private function isOutOfAllowedRange(string $amount): bool
    {
        return bccomp($this->amountMin, $amount) === 1 || bccomp($amount, $this->amountMax) === 1;
    }
}
