<?php

namespace App\Http\Controllers\API;

use App\User;
use App\Account;
use App\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;

/**
 * Контроллер создания клиента с кошельком.
 *
 * @package App\Http\Controllers\API
 */
class RegisterController extends Controller
{
    private const VALIDATION_RULES = [
        'name' => 'required|unique:users|min:1|max:50',
        'city' => 'required|min:1|max:50',
        'country' => 'required|min:1|max:80',
        'currency_iso' => 'required|string|size:3',
    ];

    /**
     * @param Request $request
     *
     * @return UserResource
     */
    public function register(Request $request): UserResource
    {
        $validatedData = $request->validate(self::VALIDATION_RULES);

        $name = $validatedData['name'];
        $city = $validatedData['city'];
        $country = $validatedData['country'];
        $currency = Currency::whereIso($validatedData['currency_iso'])->first();

        if (null === $currency) {
            abort(404, 'Currency not found.');
        }

        $user = DB::transaction(
            function () use ($name, $city, $country, $currency) {
                $user = new User();
                $user->name = $name;
                $user->city = $city;
                $user->country = $country;
                $user->save();

                $user->account()->save(
                    new Account(['currency_id' => $currency->id])
                );

                return $user;
            }
        );

        return new UserResource($user);
    }
}
