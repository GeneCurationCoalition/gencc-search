<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use Notifiable;
    use HasFactory;



    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function scopeUuid($query, $id)
    {
        return $query->where('uuid', '=', $id)->orderBy('updated_at', 'asc');
    }

    protected static function boot()
    {
        parent::boot();

        // Do this when creating...
        // The key item below is finding matching the uuid for the document time to a documenttype id
        static::creating(function ($model) {
            $model->handle                  = Str::uuid();
            $model->uuid                    = Str::uuid();
        });
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'handle',
        'type',
        'status'
    ];
}
