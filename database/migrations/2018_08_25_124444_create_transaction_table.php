<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction', function (Blueprint $table) {
            $table->text('hash');
            $table->text('sender_id');
            $table->text('sender_amount');
            $table->string('reciver_phone')->nullable();
            $table->string('reciver_account_id')->nullable();
            $table->text('reciver_account_bank')->nullable();
            $table->text('reciver_account_name')->nullable();
            $table->text('note');
            $table->text('type');
            $table->text('prevhash')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaction');
    }
}
