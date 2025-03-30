<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiModel extends Model
{
    use HasFactory;
    const STATUS_INACTIVE = 0; // Inactive
    const STATUS_ACTIVE = 1; // Active

    protected $table = 'models';

    protected $fillable = ['id', 'name', 'url', 'status', 'config'];

    public function setConfigAttribute($value)
    {
        $this->attributes['config'] = is_array($value)
            ? json_encode($value)
            : $value;
    }

    public function getConfigAttribute($value)
    {
        return is_string($value)
            ? json_decode($value, true)
            : $value;
    }

    public function cameras()
    {
        return $this->hasMany(Camera::class, 'model_id');
    }
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeInActive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }
}