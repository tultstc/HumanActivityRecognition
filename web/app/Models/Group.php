<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $table = 'groups';

    protected $fillable = ['name', 'description'];

    public function cameras()
    {
        return $this->belongsToMany(Camera::class, 'camera_group', 'group_id', 'camera_id');
    }
}