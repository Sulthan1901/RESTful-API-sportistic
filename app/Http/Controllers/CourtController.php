<?php

namespace App\Http\Controllers;

use App\Models\Court;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CourtController extends Controller
{
  public function index(Request $request)
  {
    $query = Court::query();

    if ($request->has('type')) {
      $query->where('type', $request->type);
    }

    if ($request->has('available')) {
      $query->where('available', $request->boolean('available'));
    }

    if ($request->has('location')) {
      $query->where('location', 'like', '%' . $request->location . '%');
    }

    $courts = $query->paginate(10);

    return response()->json([
      'data' => $courts->items(),
      'meta' => [
        'current_page' => $courts->currentPage(),
        'last_page' => $courts->lastPage(),
        'per_page' => $courts->perPage(),
        'total' => $courts->total(),
      ]
    ]);
  }

  public function show($id)
  {
    $court = Court::find($id);

    if (!$court) {
      return response()->json(['message' => 'Court not found'], 404);
    }

    return response()->json(['data' => $court]);
  }


  public function store(Request $request)
  {
    $validated = $request->validate([
      'name' => 'required|string|max:255',
      'type' => 'required|string|max:255',
      'location' => 'required|string|max:255',
      'facilities' => 'nullable|array',
      'price_per_hour' => 'required|numeric|min:0',
      'available' => 'boolean',
    ]);

    $court = Court::create($validated);
    return response()->json(['data' => $court], 201);
  }

  public function update(Request $request, $id)
  {
    $court = Court::findOrFail($id);

    $validated = $request->validate([
      'name' => 'sometimes|string|max:255',
      'type' => 'sometimes|string|max:255',
      'location' => 'sometimes|string|max:255',
      'facilities' => 'sometimes|array',
      'price_per_hour' => 'sometimes|numeric|min:0',
      'available' => 'sometimes|boolean',
    ]);

    $court->update($validated);
    return response()->json(['data' => $court]);
  }

  public function destroy($id)
  {
    $court = Court::findOrFail($id);
    $court->delete();
    return response()->json(['message' => 'Berhasil menghapus court'], 200);
  }
}
