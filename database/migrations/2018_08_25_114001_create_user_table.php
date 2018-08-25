<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user', function (Blueprint $table) {
            $table->text('id');
            $table->text('name');
            $table->text('citizen_id');
            $table->string('phone');
            $table->string('account_id');
            $table->bigInteger('balance');
            $table->text('fingerprint');
            $table->text('signature');
            $table->text('pin');
            $table->timestamps();
            $table->softDeteles();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user');
    }
}
