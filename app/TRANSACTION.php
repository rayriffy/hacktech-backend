<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TRANSACTION extends Model
{
  use SoftDeletes;

  protected $table = 'transaction';

  protected $fillable = [
    'hash',
    'sender_id',
    'sender_amount',
    'reciver_phone',
    'reciver_account_id',
    'reciver_account_bank',
    'reciver_account_name',
    'note',
    'type',
    'prevhash'
  ];

  protected $hidden = [
    'deleted_at',
  ];

  protected $dates = ['deleted_at'];
}