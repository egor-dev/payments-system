<?php

namespace App;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Клиент.
 *
 * @property int $id
 * @property string $name
 * @property string $city
 * @property string $country
 * @property Account $account
 *
 * @package App
 */
class User extends Authenticatable
{
    protected $casts = [
        'id' => 'integer',
    ];

    protected $guarded = [];

    /**
     * Клиент имеет один кошелек.
     *
     * @return HasOne
     */
    public function account(): HasOne
    {
        return $this->hasOne(Account::class);
    }
}
