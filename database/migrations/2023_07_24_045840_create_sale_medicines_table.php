<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sale_medicines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('medicine_bill_id');
            $table->unsignedBigInteger('medicine_id');
            $table->integer('sale_quantity');
            $table->float('sale_price');
            $table->float('tax');
            $table->dateTime('expiry_date');
            $table->float('amount');
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
        Schema::dropIfExists('sale_medicines');
    }
};
