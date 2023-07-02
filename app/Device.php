<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;


    protected $fillable = ['name', 'serial_number'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
