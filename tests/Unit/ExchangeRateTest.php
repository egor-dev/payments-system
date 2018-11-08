<?php

namespace Tests\Unit;

use App\Currency;
use Carbon\Carbon;
use Tests\TestCase;
use App\ExchangeRate;
use App\Exceptions\ExchangeRateCreationException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ExchangeRateTest extends TestCase
{
    use DatabaseMigrations, DatabaseTransactions;

    protected function setUp()
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class'=>'CurrenciesTableSeeder']);
    }

    public function test_it_throws_exception_if_rate_less_than_0_0001()
    {
        $rur = Currency::whereIso('rur')->first();
        $usd = Currency::whereIso('usd')->first();

        $date = Carbon::now()->toDateString();

        $this->expectException(ExchangeRateCreationException::class);
        $this->expectExceptionMessage('Rates less than 0.00001 not allowed.');

        ExchangeRate::create($rur, $usd, '0.000009', $date);
    }

    public function test_it_throws_exception_if_date_past()
    {
        $rur = Currency::whereIso('rur')->first();
        $usd = Currency::whereIso('usd')->first();

        $this->expectException(ExchangeRateCreationException::class);
        $this->expectExceptionMessage('Date must be today or further.');

        ExchangeRate::create($rur, $usd, 1, Carbon::yesterday()->toDateString());
        ExchangeRate::create($rur, $usd, 1, Carbon::now()->subSecond()->toDateString());
    }

    public function test_it_throws_exception_if_usd_is_first_currency_or_usd_not_second_currency()
    {
        $rur = Currency::whereIso('rur')->first();
        $usd = Currency::whereIso('usd')->first();
        $eur = Currency::whereIso('eur')->first();

        $this->expectException(ExchangeRateCreationException::class);
        $this->expectExceptionMessage('Only USD allowed as variable currency.');
        ExchangeRate::create($usd, $rur, 1, Carbon::now()->toDateString());

        $this->expectException(ExchangeRateCreationException::class);
        $this->expectException('USD not allowed as fixed currency.');
        ExchangeRate::create($rur, $eur, 1, Carbon::now()->toDateString());
    }

    public function test_it_creates_exchange_rate()
    {
        $rur = Currency::whereIso('rur')->first();
        $usd = Currency::whereIso('usd')->first();

        $date = Carbon::now()->toDateString();
        ExchangeRate::create($rur, $usd, '0.015', $date);

        $this->assertDatabaseHas('exchange_rates', [
            'id' => 1,
            'rate' => '0.015',
            'fixed_currency_id' => $rur->id,
            'variable_currency_id' => $usd->id,
            'date' => $date,
        ]);
    }

    /**
     * @depends test_it_creates_exchange_rate
     */
    public function test_it_throws_exception_if_rate_already_exists_on_specifed_date()
    {
        $rur = Currency::whereIso('rur')->first();
        $usd = Currency::whereIso('usd')->first();
        $date = Carbon::now()->toDateString();
        ExchangeRate::create($rur, $usd, '0.015', $date);

        $this->expectException(ExchangeRateCreationException::class);
        $this->expectExceptionMessage('Exchange rate for this date already exists.');
        ExchangeRate::create($rur, $usd, '0.015', $date);
    }
}
