<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FixTransactionTable extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('transaction', function (Blueprint $table) {
      $table->renameColumn('transaction_id', 'id');
      $table->renameColumn('sender_ammount', 'sender_amount');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('transaction', function (Blueprint $table) {
      $table->dropColumn('id');
      $table->dropColumn('sender_amount');
    });
  }
}
