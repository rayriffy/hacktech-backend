<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SHOP extends Model
{
    use SoftDeletes;

    protected $table = 'shop';

    protected $fillable = [
    ];

    protected $hidden = [
      'deleted_at',
    ];

    protected $dates = ['deleted_at'];
}
