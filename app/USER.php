<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class USER extends Model
{
  use SoftDeletes;

  protected $table = 'user';

  protected $fillable = [
    'id',
    'name',
    'citizen_id',
    'phone',
    'account_id',
    'balance',
    'fingerprint',
    'signature',
    'pin'
  ];

  protected $hidden = [
    'deleted_at',
  ];

  protected $dates = ['deleted_at'];
}