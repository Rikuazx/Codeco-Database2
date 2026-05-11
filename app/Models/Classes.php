<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classes extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'total_sessions',
    ];

    public function sessions()
    {
        return $this->hasMany(ClassSession::class, 'class_id');
    }
}
