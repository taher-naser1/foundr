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
        Schema::create('purchase_medicines', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_no');
            $table->float('tax');
            $table->float('total');
            $table->float('net_amount');
            $table->integer('payment_type');
            $table->float('discount');
            $table->string('note')->nullable();
            $table->string('payment_note')->nullable();
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
        Schema::dropIfExists('purchase_medicines');
    }
};
