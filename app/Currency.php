<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Валюта.
 *
 * @property int $id
 * @property string $name
 * @property string $iso стандартное сокращение
 * @property string $sign знак
 *
 * @package App
 */
class Currency extends Model
{
    protected $casts = [
        'id' => 'integer',
    ];

    protected $guarded = [];

    /**
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'iso';
    }
}
