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
        Schema::create('prescriptions_medicines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prescription_id');
            $table->unsignedBigInteger('medicine');
            $table->string('dosage')->nullable();
            $table->string('day')->nullable();
            $table->integer('dose_interval');
            $table->string('time')->nullable();
            $table->string('comment')->nullable();

            $table->foreign('prescription_id')->references('id')->on('prescriptions')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('medicine')->references('id')->on('medicines')
                ->onDelete('cascade')
                ->onUpdate('cascade');
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
        Schema::dropIfExists('prescriptions_medicines');
    }
};
