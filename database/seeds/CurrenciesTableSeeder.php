<?php

use Illuminate\Database\Seeder;

class CurrenciesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $currencies = [
            ['United States dollar', 'usd', '$'],
            ['Euro', 'eur', '€'],
            ['Japanese yen', 'jpy', '¥'],
            ['Pound sterling', 'gbp', '£'],
            ['Australian dollar', 'aud', 'A$'],
            ['Canadian dollar', 'cad', 'C$'],
            ['Swiss franc', 'chf', 'Fr'],
            ['Russian rouble', 'rur', '₽'],
        ];

        foreach ($currencies as $data) {
            $currency = new \App\Currency(
                [
                    'name' => $data[0],
                    'iso' => $data[1],
                    'sign' => $data[2],
                ]
            );
            $currency->save();
        }
    }
}
