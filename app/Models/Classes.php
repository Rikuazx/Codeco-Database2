<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classes extends Model
{
    protected $fillable = [
    'class_id',
    'start_time',
    'end_time',
    'teacher_id'
];
public function sessions()

{
    
    return $this->hasMany(ClassSession::class);
}
}
