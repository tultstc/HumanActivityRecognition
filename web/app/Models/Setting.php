<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'last_cleanup_at'];

    protected $casts = [
        'last_cleanup_at' => 'datetime'
    ];
}