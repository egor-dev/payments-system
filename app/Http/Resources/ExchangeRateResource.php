<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class ExchangeRateResource.
 *
 * @package App\Http\Resources
 */
class ExchangeRateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
            'rate' => $this->rate,
            'date' => $this->date,
            'fixed_currency' => new CurrencyResource($this->fixedCurrency),
            'variable_currency' => new CurrencyResource($this->variableCurrency),
        ];
    }
}
