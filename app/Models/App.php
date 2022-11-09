<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class App extends Model
{
    use softDeletes;


    protected $table = 'apps';

    protected $guarded = ['id'];
    protected $fillable = ['name', 'description', 'user_id', 'active'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function phones()
    {
        return $this->hasMany(Phone::class, 'app_id', 'id');
    }


}
