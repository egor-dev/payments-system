<?php

use Illuminate\Database\Seeder;

class TransactionTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        (new \App\TransactionType(['name' => 'Top up']))->save();
        (new \App\TransactionType(['name' => 'Transfer']))->save();
    }
}
