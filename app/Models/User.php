<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function setFirstnameAttribute($value) {
        return $this->attributes['firstname'] = ucfirst($value);
    }

    public function setLastnameAttribute($value) {
        return $this->attributes['lastname'] = ucfirst($value);
    }

    public function saveUserDetails($validator)
    {
        $user = User::create($validator);
        return $user;
    }


    public function userEmailValidation($email)
    {
        $user = User::where('email', $email)->first();

        return $user;
    }

     /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function notes()
    {
        return $this->hasMany('App\Models\Note');
    }

    public function labels()
    {
        return $this->hasMany('App\Models\Label');
    }

    public function label_notes()
    {
        return $this->hasMany('App\Models\LabelNotes');
    }

    public function collaborators()
    {
        return $this->hasMany('App\Models\Collaborator');
    }
}


