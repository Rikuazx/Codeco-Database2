<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ClassSession;

class Classes extends Model
{
    protected $fillable = [
        'class_id',
        'start_time',
        'end_time',
        'teacher_id',
        'price',
    ];
public function sessions()

{
    
    return $this->hasMany(ClassSession::class);
}
}
