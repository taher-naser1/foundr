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
        Schema::create('used_medicines', function (Blueprint $table) {
            $table->id();
            $table->integer('stock_used');
            $table->unsignedBigInteger('medicine_id')->nullable();
            $table->integer('model_id');
            $table->string('model_type');
            $table->timestamps();

            $table->foreign('medicine_id')
                ->references('id')
                ->on('medicines')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('used_medicines');
    }
};
