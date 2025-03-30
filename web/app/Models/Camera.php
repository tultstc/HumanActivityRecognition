<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Camera extends Model
{
    use HasFactory;
    const STATUS_INACTIVE = 0; // Inactive
    const STATUS_ACTIVE = 1; // Active

    protected $table = 'cameras';

    protected $fillable = ['id', 'name', 'stream_url', 'status', 'config', 'model_id'];

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

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeInActive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    public function position()
    {
        return $this->belongsTo(Position::class, 'vitriid');
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'nhomid');
    }

    public function model()
    {
        return $this->belongsTo(AiModel::class, 'model_id');
    }

    public function scopePublished($query)
    {
        $query->where('created_at', '<=', Carbon::now());
    }

    public function getRtspUrl()
    {
        return sprintf(
            'rtsp://%s:%s@%s:%d%s',
            $this->tendangnhap,
            Crypt::decryptString($this->matkhau),
            $this->diachiip,
            $this->cong,
            $this->duongdan
        );
    }
}