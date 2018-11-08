<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExchangeRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->increments('id');
            $table->decimal('rate', 5, 5);
            $table->unsignedTinyInteger('fixed_currency_id');
            $table->unsignedTinyInteger('variable_currency_id');
            $table->date('date');
            $table->timestamps();

            $table->foreign('fixed_currency_id')->references('id')->on('currencies');
            $table->foreign('variable_currency_id')->references('id')->on('currencies');

            // для выборки в конвертере валют в момент совершения операций
            $table->index(['date', 'fixed_currency_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('exchange_rates');
    }
}
