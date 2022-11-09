<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Phone extends Model
{
    use softDeletes;

    /*
   |--------------------------------------------------------------------------
   | GLOBAL VARIABLES
   |--------------------------------------------------------------------------
   */
    protected $table = 'phones';
    //protected $primaryKey = 'id';
    // public $timestamps = false;
    // protected $guarded = ['id'];
    protected $fillable = [
        'mac',
        'app_id',
        's1',
        's2',
        's3',
        's4',
        's5',
        's6',
        's7',
        's8',
        'h1',
        'h2',
        'hasOrder',
        'notes',
        'updated_at',
        'updated_LastFile',
    ];
    // protected $hidden = [];
    protected $dates = ['updated_at', 'deleted_at'];

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */
    protected static function boot()
    {
        parent::boot();

        /*   static::updating(function ($table) {
               $table->updated_by = \Auth::id();
           });

           static::deleting(function ($table) {
               $table->deleted_by = \Auth::id();
               $table->save();
           });

           static::restoring(function ($table) {
               $table->restored_by = \Auth::id();
           });*/
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class, 'phone_id', 'id');
    }

    public function files()
    {
        return $this->hasMany(File::class, 'phone_id', 'id');
    }
}
