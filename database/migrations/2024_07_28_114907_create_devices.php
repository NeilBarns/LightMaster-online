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
        Schema::create('Devices', function (Blueprint $table) {
            $table->bigIncrements('DeviceID');
            $table->string('DeviceName');
            $table->string('Description')->nullable();
            $table->unsignedBigInteger('DeviceStatusID');
            $table->string('IPAddress');
            $table->dateTime('OperationDate')->nullable();
            $table->timestamps();

            //Indexes
            $table->index('DeviceName');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('Devices');
    }
};
