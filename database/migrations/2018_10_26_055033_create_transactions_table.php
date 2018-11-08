<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('sender_account_id')->nullable();
            $table->unsignedInteger('receiver_account_id');
            $table->decimal('amount', 13, 2);
            $table->unsignedTinyInteger('currency_id');
            $table->unsignedTinyInteger('transaction_type_id');
            $table->timestamps();

            $table->foreign('sender_account_id')->references('id')->on('accounts');
            $table->foreign('receiver_account_id')->references('id')->on('accounts');
            $table->foreign('currency_id')->references('id')->on('currencies');
            $table->foreign('transaction_type_id')->references('id')->on('transaction_types');

            // выборка для проверки ограничения на частые переводы с одного кошелька
            $table->index(['sender_account_id', 'transaction_type_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
