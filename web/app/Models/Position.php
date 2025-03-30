<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    use HasFactory;

    protected $table = 'vitri';

    protected $fillable = ['ten', 'ma', 'mota', 'khuvucid'];

    public function area()
    {
        return $this->belongsTo(Area::class, 'khuvucid');
    }

    public function cameras()
    {
        return $this->hasMany(Camera::class, 'vitriid');
    }
}
