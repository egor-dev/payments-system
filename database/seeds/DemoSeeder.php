<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Psr\Http\Message\ResponseInterface;

class DemoSeeder extends Seeder
{
    private $client;

    public function __construct()
    {
        $this->client = new \GuzzleHttp\Client();
    }

    /**
     * Run the database seeds.
     *
     * @throws Exception
     *
     * @return void
     */
    public function run()
    {
        Artisan::call('migrate:fresh');
        Artisan::call('db:seed', ['--class'=> 'CurrenciesTableSeeder']);
        Artisan::call('db:seed', ['--class'=> 'TransactionTypesTableSeeder']);

        $faker = Faker\Factory::create();
        $currencies = \App\Currency::all()->keyBy('iso');

        $todayDate = \Carbon\Carbon::now()->toDateString();
        config(['app.frequency.times_in_period' => 6000]);

        unset($currencies['usd']);

        foreach ($currencies as $currency) {
            $uri = route('exchange_rate', ['iso' => $currency->iso]);
            $response = $this->request(
                $uri,
                [
                    'rate' => (string) ($faker->numberBetween(1, 999) / 1000),
                    'date' => $todayDate,
                ]
            );

            echo "$uri\n";
            $this->dumpResponse($response);
        }
        echo "\n\n";

        for ($i = 0; $i < 10; $i++) {
            $uri = route('registration');
            $response = $this->request($uri, [
                'name' => $faker->name,
                'city' => $faker->city,
                'country' => $faker->country,
                'currency_iso' => $currencies->random()->iso,
            ]);
            echo "$uri\n";
            $this->dumpResponse($response);
        }
        echo "\n\n";

        $accounts = \App\Account::all();
        foreach ($accounts as $senderAccount) {
            $accountId = $senderAccount->id;
            $uri = route('topup', ['account' => $accountId]);
            $response = $this->request($uri, [
                'amount' => (string) random_int(1000, 50000),
            ]);
            echo "$uri\n";
            $this->dumpResponse($response);
        }
        echo "\n\n";

        if (env('QUEUE_CONNECTION') !== 'sync') {
            // подождем пока обработаются пополнения
            sleep(5);
        }

        $accounts = $accounts->fresh()->keyBy('id');

        foreach (range(0, 100) as $i) {

            /** @var \App\Account $senderAccount */
            /** @var \App\Account $receiverAccount */
            $senderAccount = $accounts->random();
            $receiverAccount = $accounts->except($senderAccount->id)->random();
            $amount = random_int(1, $senderAccount->balance/4);
            if ($amount > 100) {
                $amount = round($amount / 100) * 100;
            }

            $uri = route(
                'transfer',
                ['senderAccount' => $senderAccount->id, 'receiverAccount' => $receiverAccount->id]
            );
            $response = $this->request($uri, [
                'amount' => (string) $amount,
                'in_currency_of' => 'sender',
            ]);
            echo "$uri\n";
            $this->dumpResponse($response);

            $accounts = $accounts->fresh();
        }
    }

    /**
     * @return array
     */
    private function headers(): array
    {
        return [
            'accept' => 'application/json',
            'content-type' => 'application/json',
        ];
    }

    /**
     * @param string $route
     * @param array $data
     *
     * @return ResponseInterface
     */
    private function request(string $route, array $data): ResponseInterface
    {
        return $this->client->post(
            $route,
            [
                'headers' => $this->headers(),
                'json' => $data,
            ]
        );
    }

    /**
     * @param $response
     */
    private function dumpResponse($response): void
    {
        $jsonDecode = json_decode($response->getBody()->getContents());
        dump($jsonDecode);
    }
}
