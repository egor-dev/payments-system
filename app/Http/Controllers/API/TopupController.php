<?php

namespace App\Http\Controllers\API;

use App\Account;
use App\Jobs\CreateTopup;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Exceptions\TopupException;

/**
 * Контроллер пополнения кошелька.
 *
 * @package App\Http\Controllers\API
 */
class TopupController
{
    /**
     * @param Account $account
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function topup(Account $account, Request $request): JsonResponse
    {
        $amountMin = config('app.topup.amount.min');
        $amountMax = config('app.topup.amount.max');

        $validatedData = $request->validate(
            [
                'amount' => "required|string|numeric|min:$amountMin|max:$amountMax",
            ]
        );

        try {
            dispatch(
                new CreateTopup($account, $validatedData['amount'])
            );
        } catch (TopupException $exception) {
            abort(400, $exception->getMessage());
        }

        return response()->json(['message' => 'Transaction is being processed.'], 202);
    }
}
