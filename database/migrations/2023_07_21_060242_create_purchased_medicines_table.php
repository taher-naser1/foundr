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
        Schema::create('purchased_medicines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_medicines_id');
            $table->unsignedBigInteger('medicine_id')->nullable();
            $table->dateTime('expiry_date')->nullable();
            $table->string('lot_no');
            $table->float('tax');
            $table->integer('quantity');
            $table->float('amount');
            $table->timestamps();
            
            $table->foreign('medicine_id')->references('id')->on('medicines')
                ->onDelete('set null')
                ->onUpdate('cascade');
            $table->foreign('purchase_medicines_id')->references('id')->on('purchase_medicines')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchased_medicines');
    }
};
