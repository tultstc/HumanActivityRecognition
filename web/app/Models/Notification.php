<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notification';

    protected $fillable = ['status', 'start_error_time', 'end_error_time', 'url', 'camera_id'];

    public function scopePublished($query)
    {
        $query->where('created_at', '<=', Carbon::now());
    }

    public function camera()
    {
        return $this->belongsTo(Camera::class, 'camera_id');
    }
}