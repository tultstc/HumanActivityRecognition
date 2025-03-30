<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Layout extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'grid_rows', 'grid_columns', 'selected_camera_ids'];

    protected $casts = [
        'selected_camera_ids' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}