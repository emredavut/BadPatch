<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class File extends Model
{

    use softDeletes;

    protected $table = 'files';
    protected $guarded = ['id'];
    protected $fillable = [];
    protected $touches = ['phone'];


    public function phone()
    {
        return $this->belongsTo(Phone::class, 'phone_id', 'id');
    }
}
