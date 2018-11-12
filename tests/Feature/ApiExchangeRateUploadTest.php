<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiExchangeRateUpload extends TestCase
{
    use DatabaseMigrations, DatabaseTransactions;

    protected function setUp()
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'CurrenciesTableSeeder']);
    }

    public function test_can_upload_exchange_rate()
    {
        $this->json(
            'POST',
            '/api/exchange_rates/rur/usd',
            [
                'rate' => '500', // 0.50rub
                'date' => Carbon::now()->toDateString(),
            ]
        )
            ->assertJson(
                [
                    'data' => [
                        'rate' => '500.000',
                        'date' => Carbon::now()->toDateString(),
                    ],
                ]
            )
            ->assertStatus(201);
    }

    public function test_can_not_upload_usd_to_usd_exchange_rate()
    {
        $this->json(
            'POST',
            '/api/exchange_rates/usd/usd',
            [
                'rate' => '500', // 0.50rub
                'date' => Carbon::now()->toDateString(),
            ]
        )
            ->assertSee('You can not store usd/usd exchange rate.')
            ->assertStatus(400);
    }

    public function test_can_not_upload_exchange_rate_for_yesterday()
    {
        $this->json(
            'POST',
            '/api/exchange_rates/rur/usd',
            [
                'rate' => '500', // 0.50rub
                'date' => Carbon::yesterday()->toDateString(),
            ]
        )
            ->assertSee('The date must be a date after or equal to')
            ->assertStatus(422);
    }

    public function test_can_not_upload_exchange_rate_for_date_two_days_ago()
    {
        $this->json(
            'POST',
            '/api/exchange_rates/rur/usd',
            [
                'rate' => '500', // 0.50rub
                'date' => Carbon::now()->subDays(2)->toDateString(),
            ]
        )
            ->assertSee('The date must be a date after or equal to')
            ->assertStatus(422);
    }
}
