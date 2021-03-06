<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BANK extends Model
{
    use SoftDeletes;

    protected $table = 'bank';

    protected $fillable = [
    'bank_id',
    'name',
    'provider',
    'balance',
  ];

    protected $hidden = [
    'deleted_at',
  ];

    protected $dates = ['deleted_at'];
}
