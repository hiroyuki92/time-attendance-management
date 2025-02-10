<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBreakTimesModificationRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('break_mod_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_mod_request_id')->constrained('attendance_mod_requests')->onDelete('cascade');
            $table->foreignId('break_times_id')->nullable()->constrained()->onDelete('cascade');
            $table->integer('temp_index')->nullable();
            $table->dateTime('requested_break_start')->nullable();
            $table->dateTime('requested_break_end')->nullable();
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
        Schema::dropIfExists('break_mod_requests');
    }
}
