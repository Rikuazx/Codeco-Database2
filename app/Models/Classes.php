<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classes extends Model
{
  protected $fillable = [
    'name',
    'total_sessions',
    'price'
];
public function sessions()

{
    return $this->hasMany(\App\Models\ClassSession::class, 'class_id');

    return $this->hasMany(ClassSession::class);
}
}
