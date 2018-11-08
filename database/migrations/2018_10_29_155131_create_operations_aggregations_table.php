<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOperationsAggregationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('operations_aggregations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->decimal('sum', 13, 2);
            $table->decimal('sum_usd', 13, 2);
            $table->date('date');
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts');

            // для подсчета сумм за период при формировании отчета
            $table->index(['account_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('operations_aggregations');
    }
}
