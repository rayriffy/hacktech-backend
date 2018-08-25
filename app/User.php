<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model
{
    use SoftDeletes;

    protected $table = 'user';

    protected $fillable = [
    ];

    protected $hidden = [
      'deleted_at',
    ];

    protected $dates = ['deleted_at'];
}
