<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{

    use softDeletes;

    protected $table = 'contacts';
    protected $guarded = ['id'];
    protected $fillable = [];


    public function ph() {
        return $this->belongsTo(Phone::class, 'phone_id', 'id');
    }

}
