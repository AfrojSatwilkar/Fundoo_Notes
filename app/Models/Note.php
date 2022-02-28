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

    public function getReminderAttribute($value) {
        if($value == null) {
            return $value;
        } else {
            return Carbon::createFromFormat('Y-m-d H:i:s', $value)->format('d-m h:i');
        }
    }

    public function noteId($id) {
        return Note::where('id', $id)->first();
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
