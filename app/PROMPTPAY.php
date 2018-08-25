<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PROMPTPAY extends Model
{
    use SoftDeletes;

    protected $table = 'promptpay';

    protected $fillable = [
    'phone',
    'balance',
  ];

    protected $hidden = [
    'deleted_at',
  ];

    protected $dates = ['deleted_at'];
}
