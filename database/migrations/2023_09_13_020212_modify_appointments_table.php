<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyAppointmentsTable extends Migration
{
    public function up()
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Make 'from_time', 'from_time_type', 'to_time', 'to_time_type', and 'payable_amount' nullable
            $table->string('from_time')->nullable()->change();
            $table->string('from_time_type')->nullable()->change();
            $table->string('to_time')->nullable()->change();
            $table->string('to_time_type')->nullable()->change();
            $table->string('payable_amount')->nullable()->change();
        });
    }

    public function down()
    {
        // Revert the changes if needed
    }
};
