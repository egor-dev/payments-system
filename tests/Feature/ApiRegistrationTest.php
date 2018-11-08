<?php

namespace Tests\Feature;

use App\Currency;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiRegistrationTest extends TestCase
{
    use DatabaseMigrations, DatabaseTransactions;

    protected function setUp()
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class'=>'CurrenciesTableSeeder']);
    }

    public function test_can_create_user_with_account()
    {
        $name = 'Egor';
        $city = 'Krasnoyarsk';
        $country = 'Russia';
        $this->json(
            'POST',
            route('registration'),
            [
                'name' => $name,
                'city' => $city,
                'country' => $country,
                'currency_iso' => 'usd',
            ]
        )->assertStatus(201)->assertJson([
            'data' => [
                'name' => $name,
                'city' => $city,
                'country' => $country,
                'account' => [
                    'id' => 1,
                    'balance' => '0.00',
                    'currency' => [
                        'iso' => 'usd'
                    ]
                ]
            ]
        ]);

        $this->assertDatabaseHas('users', [
            'id' => 1,
            'name' => $name,
            'city' => $city,
            'country' => $country,
        ])->assertDatabaseHas('accounts', [
            'id' => 1,
            'user_id' => 1,
            'currency_id' => 1,
            'balance' => 0,
        ]);
    }

    public function test_can_not_create_account_with_unexisting_currency()
    {
        $name = 'Egor';
        $city = 'Krasnoyarsk';
        $country = 'Russia';
        $this->json(
            'POST',
            route('registration'),
            [
                'name' => $name,
                'city' => $city,
                'country' => $country,
                'currency_iso' => 'xxx',
            ]
        )->assertStatus(404);
    }
}
