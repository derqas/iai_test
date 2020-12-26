<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicePositionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_positions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('invoice_id');
            $table->string('name');
            $table->string('unit');
            $table->double('quantity',15,3);
            $table->double('price',15,2);
            $table->double('sum');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoce_positions');
    }
}
