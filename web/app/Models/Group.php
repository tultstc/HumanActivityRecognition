<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $table = 'nhom';

    protected $fillable = ['ten', 'mota'];

    public function cameras()
    {
        return $this->hasMany(Camera::class);
    }
}
