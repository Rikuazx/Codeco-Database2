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
        'price' => 'required|numeric',
        'total_sessions' => 'required|integer'
    ]);

    $class = \App\Models\Classes::create($request->all());

    return response()->json($class);
}


public function index()
{
    $classes = Classes::with('sessions')->get();

    return response()->json($classes);
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
    Classes::findOrFail($id)->delete();

    return response()->json(['message' => 'Deleted']);
}

}
