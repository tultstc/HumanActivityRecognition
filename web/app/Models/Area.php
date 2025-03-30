<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;

    protected $table = 'khuvuc';

    protected $fillable = ['ten', 'ma', 'mota'];

    public function positions()
    {
        return $this->hasMany(Position::class, 'khuvucid');
    }
}
