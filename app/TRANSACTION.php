<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class TRANSACTION extends Model
{
  use SoftDeletes;

  protected $table = 'transaction';

  protected $fillable = [
  ];

  protected $hidden = [
    'deleted_at'
  ];

  protected $dates = ['deleted_at'];
}
