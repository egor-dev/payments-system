<?php

namespace App\Http\Controllers\API;

use App\Account;
use App\Jobs\CreateTransfer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Exceptions\TransferException;

/**
 * Контроллер перевода между кошельками.
 *
 * @package App\Http\Controllers\API
 */
class TransferController extends Controller
{
    /**
     * @param Account $senderAccount
     * @param Account $receiverAccount
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function transfer(Account $senderAccount, Account $receiverAccount, Request $request): JsonResponse
    {
        $amountMin = config('app.transfer.amount.min');
        $amountMax = config('app.transfer.amount.max');

        $validatedData = $request->validate([
            'amount' => "required|string|numeric|between:$amountMin,$amountMax|regex:/^\d*(\.\d{1,2})?$/",
            'in_currency_of' => 'required|in:sender,receiver',
        ]);

        $transferCurrency = $validatedData['in_currency_of'] === 'sender' ?
            $senderAccount->currency :
            $receiverAccount->currency;

        try {
            dispatch(
                new CreateTransfer($validatedData['amount'], $senderAccount, $receiverAccount, $transferCurrency)
            );
        } catch (TransferException $exception) {
            abort(400, $exception->getMessage());
        }

        return response()->json(['message' => 'Transaction is being processed.'], 202);
    }
}
