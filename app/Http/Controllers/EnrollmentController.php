<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Classes;
class EnrollmentController extends Controller
{
    public function store(Request $request)
{
    return DB::transaction(function () use ($request) {

        $request->validate([
            'student_id' => 'required|exists:students,id',
            'class_id' => 'required|exists:classes,id',
        ]);

        // get class
        $class = Classes::findOrFail($request->class_id);

        $exists = Enrollment::where('student_id', $request->student_id)
        ->where('class_id', $request->class_id)
        ->exists();

        if ($exists) {
        return response()->json([
            'message' => 'Student already enrolled in this class'
        ], 400);
    }

        // create enrollment
        $enrollment = Enrollment::create([
            'student_id' => $request->student_id,
            'class_id'   => $request->class_id,
            'price'      => $class->price,
            'status'     => 'active', // langsung active karena tidak ada flow payment confirmation
        ]);

        // create payment
        Payment::create([
            'enrollment_id' => $enrollment->id,
            'amount' => $class->price,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Enrollment created',
            'data' => $enrollment
        ]);
        
    });
}

public function index()
{
    $enrollments = Enrollment::with(['student.user', 'course'])
        ->latest()
        ->get();

    return response()->json(['data' => $enrollments]);
}

}