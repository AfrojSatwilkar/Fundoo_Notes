<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    protected $table="notes";
    protected $fillable = [
        'title',
        'description'
    ];

    public function getTitleAttribute($value) {
        return ucfirst($value);
    }

    // public function setReminderAttribute($value) {
    //     $date = Carbon::createFromFormat('Y-m-d H:i:s', $value);
    //     return $date->format('Y-m-d H:i:s');
    // }

    public function getReminderAttribute($value) {
        if($value == null) {
            return $value;
        } else {
            return Carbon::createFromFormat('Y-m-d H:i:s', $value)->format('d-m h:i');
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function label()
    {
        return $this->hasMany(Label::class);
    }
}
