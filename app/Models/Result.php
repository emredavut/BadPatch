<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Result extends Model
{

    use softDeletes;

    protected $table = 'results';
    protected $guarded = ['id'];
    protected $fillable = [];

}
