<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
   protected $fillable = [
    'student_id',
    'class_id',
    'price',
    'status' => 'pending',
]; //
}
