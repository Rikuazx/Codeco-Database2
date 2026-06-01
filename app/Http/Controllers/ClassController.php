<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Classes;

class ClassController extends Controller
{
public function store(Request $request)
{
    $request->validate([
    'name' => 'required',
    'description' => 'nullable|string',
    'total_sessions' => 'required|integer',
    'price' => 'required|numeric'
]);

    $class = \App\Models\Classes::create([
    'name' => $request->name,
    'description' => $request->description,
    'total_sessions' => $request->total_sessions,
    'price' => $request->price
]);

    return response()->json([
        'message' => 'Class created successfully',
        'data' => $class
    ]);
}


public function index()
{
    return \App\Models\Classes::all();
}

public function update(Request $request, $id)
{
    $class = Classes::findOrFail($id);

    $request->validate([
        'name' => 'required',
        'description' => 'nullable|string',
        'total_sessions' => 'required|integer',
        'price' => 'required|numeric',
    ]);

    $class->update([
        'name' => $request->name,
        'description' => $request->description,
        'total_sessions' => $request->total_sessions,
        'price' => $request->price,
    ]);

    return response()->json([
        'message' => 'Class updated successfully',
        'data' => $class
    ]);
}

public function show($id)
{
    $class = Classes::with([
        'sessions.teacher.user'
    ])->findOrFail($id);

    return response()->json($class);
}
public function destroy($id)
{
    $class = \App\Models\Classes::findOrFail($id);
    $class->delete();

    return response()->json([
        'message' => 'Class deleted'
    ]);
}

}
