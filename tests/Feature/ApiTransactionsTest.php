<?php

namespace Tests\Feature;

use App\Currency;
use App\ExchangeRate;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\TestResponse;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiTransferTest extends TestCase
{
    use DatabaseMigrations, DatabaseTransactions;

    protected function setUp()
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class'=>'CurrenciesTableSeeder']);
        $this->artisan('db:seed', ['--class'=>'TransactionTypesTableSeeder']);

        $this->insertExchangeRates();
    }

    public function test_can_topup_account()
    {
        $this->createAccountAndTopup('Egor', 'rur', '100');

        $this->assertDatabaseHas('accounts', ['id' => 1, 'balance' => '100'])
            ->assertDatabaseHas(
                'transactions',
                [
                    'id' => 1,
                    'amount' => 100,
                    'sender_account_id' => null,
                    'receiver_account_id' => 1,
                    'transaction_type_id' => 1,
                ]
            )->assertDatabaseHas(
                'operations',
                [
                    'id' => 1,
                    'amount' => 100,
                ]
            )->assertDatabaseHas('operations_aggregations', [
                'account_id' => 1,
                'date' => Carbon::now()->toDateString(),
                'sum' => 100,
                'sum_usd' => 50,
            ]);
    }

    public function test_can_not_topup_negative_amount()
    {
        $this->createAccount('Egor', 'rur');
        $this->json('POST', '/api/topup/1', ['amount' => -100])->assertStatus(422);
    }

    public function test_can_transfer_from_rub_account_to_usd_account_in_roubles()
    {
        $this->createAccountAndTopup('Egor', 'rur', 1000);
        $this->createAccount('Vasya', 'usd');

        $this->json('POST', $this->transferRoute(1, 2), ['amount' => '600.02', 'in_currency_of' => 'sender'])
            ->assertStatus(202)
            ->assertJson(['message' => 'Transaction is being processed.']);

        $this->assertDatabaseHas('accounts', ['id' => 1, 'balance' => '399.98'])
            ->assertDatabaseHas('accounts', ['id' => 2, 'balance' => '300.01'])
            ->assertDatabaseHas(
                'transactions',
                [
                    'id' => 2,
                    'transaction_type_id' => 2,
                    'amount' => 600.02,
                    'sender_account_id' => '1',
                    'receiver_account_id' => '2',
                ]
            )->assertDatabaseHas(
                'operations',
                [
                    'id' => 2,
                    'transaction_id' => '2',
                    'amount' => -600.02,
                ]
            )->assertDatabaseHas(
                'operations',
                [
                    'id' => 3,
                    'transaction_id' => '2',
                    'amount' => 300.01,
                ]
            )->assertDatabaseHas('operations_aggregations', [
                'account_id' => 1,
                'date' => Carbon::now()->toDateString(),
                'sum' => 1600.02,
                'sum_usd' => 800.01,
            ]);
    }

    public function test_can_transfer_from_rub_account_to_usd_account_in_usd()
    {
        $this->createAccountAndTopup('Egor', 'rur', 1000);
        $this->createAccount('Vasya', 'usd');

        $this->json('POST', $this->transferRoute(1, 2), ['amount' => '500', 'in_currency_of' => 'receiver'])
            ->assertStatus(202)
            ->assertJson(['message' => 'Transaction is being processed.']);

        $this->assertDatabaseHas('accounts', ['id' => 1, 'balance' => 0])
            ->assertDatabaseHas('accounts', ['id' => 2, 'balance' => 500])
            ->assertDatabaseHas(
                'transactions',
                [
                    'id' => 2,
                    'transaction_type_id' => 2,
                    'amount' => '500',
                    'sender_account_id' => '1',
                    'receiver_account_id' => '2',
                ]
            );
    }

    public function test_can_transfer_from_euro_account_to_rub_account_in_euro()
    {
        $this->createAccountAndTopup('Egor', 'rur', 1000);
        $this->createAccount('Vasya', 'eur');

        $this->json('POST', $this->transferRoute(1, 2), ['amount' => '250', 'in_currency_of' => 'receiver'])
            ->assertStatus(202)
            ->assertJson(['message' => 'Transaction is being processed.']);

        $this->assertDatabaseHas('accounts', ['id' => 1, 'balance' => 0])
            ->assertDatabaseHas('accounts', ['id' => 2, 'balance' => 250]);
    }

    public function test_cant_transfer_from_euro_account_to_rub_account_in_usd()
    {
        $this->createAccountAndTopup('Egor', 'rur', 1000);
        $this->createAccount('Vasya', 'eur');

        $this->json('POST', $this->transferRoute(1, 2), ['amount' => '250', 'in_currency_of' => 'receiver'])
            ->assertStatus(202)
            ->assertJson(['message' => 'Transaction is being processed.']);

        $this->assertDatabaseHas('accounts', ['id' => 1, 'balance' => 0])
            ->assertDatabaseHas('accounts', ['id' => 2, 'balance' => 250]);
    }

    public function test_cant_transfer_when_not_enough_money()
    {
        $this->createAccountAndTopup('Egor', 'rur', 1000);
        $this->createAccount('Vasya', 'rur');

        $this->json('POST', $this->transferRoute(1, 2), ['amount' => '1500', 'in_currency_of' => 'sender'])
            ->assertStatus(400);

        $this->json('POST', $this->transferRoute(1, 2), ['amount' => '1500', 'in_currency_of' => 'receiver'])
            ->assertStatus(400);
    }

    public function test_cant_transfer_negative_amount()
    {
        $this->createAccountAndTopup('Egor', 'rur', 1000);
        $this->createAccount('Vasya', 'rur');

        $this->json('POST', $this->transferRoute(1, 2), [
            'amount' => '-100',
            'from_account_id' => 1,
            'initiator_user_id' => 1,
            'in_currency_iso' => 'rur',
        ])->assertStatus(422);
    }

    public function test_cant_transfer_very_small_amount()
    {
        $this->createAccountAndTopup('Egor', 'rur', 1000);
        $this->createAccount('Vasya', 'usd');

        $this->json('POST', $this->transferRoute(1, 2), ['amount' => '1.50', 'in_currency_of' => 'sender'])
            ->assertStatus(400);

        $this->assertDatabaseMissing(
                'transactions',
                [
                    'id' => 2,
                    'transaction_type_id' => 2,
                    'amount' => '1.50',
                    'sender_account_id' => '1',
                    'receiver_account_id' => '2',
                ]
            );
    }

    public function test_cant_transfer_frequently()
    {
        $this->createAccountAndTopup('Egor', 'rur', 1000);
        $this->createAccount('Vasya', 'usd');

        $this->json('POST', $this->transferRoute(1, 2), ['amount' => '100', 'in_currency_of' => 'sender'])
            ->assertStatus(202);
        $this->json('POST', $this->transferRoute(1, 2), ['amount' => '100', 'in_currency_of' => 'sender'])
            ->assertStatus(202);
        $this->json('POST', $this->transferRoute(1, 2), ['amount' => '100', 'in_currency_of' => 'sender'])
            ->assertStatus(400);
    }

    /**
     * @param string $name
     * @param string $accountCurrencyIso
     *
     * @return TestResponse
     */
    private function createAccount(string $name, string $accountCurrencyIso): TestResponse
    {
        $json = $this->json(
            'POST',
            route('registration'),
            [
                'name' => $name,
                'city' => 'Some city',
                'country' => 'Some country',
                'currency_iso' => $accountCurrencyIso,
            ]
        );

        $json->assertStatus(201);

        return $json;
    }

    /**
     * @param string $name
     * @param string $accountCurrencyIso
     * @param string $topupAmount
     *
     * @return TestResponse
     */
    private function createAccountAndTopup(string $name, string $accountCurrencyIso, string $topupAmount)
    {
        $json = $this->createAccount($name, $accountCurrencyIso)->getContent();
        $response = json_decode($json, true);
        $accountId = $response['data']['account']['id'];

        return $this->topupAccount($accountId, $topupAmount);
    }

    private function topupAccount(int $accountId, string $amount): TestResponse
    {
        $json = $this->json('POST', route('topup', ['account'=>$accountId]), ['amount' => $amount]);
        $json->assertStatus(202);

        return $json;
    }

    /**
     * @param int $senderAccountId
     * @param int $receiverAccountId
     *
     * @return string
     */
    private function transferRoute(int $senderAccountId, int $receiverAccountId): string
    {
        return route('transfer', ['senderAccount'=>$senderAccountId, 'receiverAccount'=>$receiverAccountId]);
    }

    private function insertExchangeRates()
    {
        $currencies = Currency::all()->keyBy('iso');

        ExchangeRate::query()->insert([
            'date' => Carbon::now()->toDateString(),
            'fixed_currency_id' => $currencies['rur']->id,
            'variable_currency_id' => $currencies['usd']->id,
            'rate' => 0.500, // 0.50 rur/usd
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        ExchangeRate::query()->insert([
            'date' => Carbon::now()->toDateString(),
            'fixed_currency_id' => $currencies['eur']->id,
            'variable_currency_id' => $currencies['usd']->id,
            'rate' => 2.000, // 2 eur/usd
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
