<?php

namespace App\Http\Controllers;

use App\Models\Komunitas;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class KomunitasController extends Controller
{
  public function index(Request $request)
  {
    $query = Komunitas::query()->withCount('members');

    // Search by name
    if ($request->has('search')) {
      $query->where('nama_komunitas', 'like', '%' . $request->search . '%');
    }

    $komunitas = $query->paginate(10);

    return response()->json([
      'data' => $komunitas->items(),
      'meta' => [
        'current_page' => $komunitas->currentPage(),
        'last_page' => $komunitas->lastPage(),
        'per_page' => $komunitas->perPage(),
        'total' => $komunitas->total(),
      ]
    ]);
  }

  public function store(Request $request)
  {
    $validated = $request->validate([
      'nama_komunitas' => 'required|string|max:255|unique:komunitas',
      'deskripsi_komunitas' => 'required|string',
      'batas_anggota' => 'required|integer|min:1',
    ]);

    $komunitas = Komunitas::create([
      'nama_komunitas' => $validated['nama_komunitas'],
      'deskripsi_komunitas' => $validated['deskripsi_komunitas'],
      'creator_id' => auth()->id(),
      'jumlah_anggota' => 0, // Set awal ke 0
      'batas_anggota' => $validated['batas_anggota'],
    ]);

    // Add creator as a member automatically
    $komunitas->members()->attach(auth()->id(), ['status' => 'accepted']);
    $komunitas->increment('jumlah_anggota'); // Increment sekali saja menjadi 1

    return response()->json(['data' => $komunitas], 201);
  }

  public function show($id)
  {
    $komunitas = Komunitas::with(['creator', 'members' => function ($query) {
      $query->where('status', 'accepted');
    }])->findOrFail($id);

    return response()->json(['data' => $komunitas]);
  }

  public function update(Request $request, $id)
  {
    $komunitas = Komunitas::findOrFail($id);

    $validated = $request->validate([
      'nama_komunitas' => 'sometimes|string|max:255|unique:komunitas,nama_komunitas,' . $id,
      'deskripsi_komunitas' => 'sometimes|string',
      'batas_anggota' => 'sometimes|integer|min:' . $komunitas->jumlah_anggota, // Batas minimum harus >= jumlah anggota saat ini
    ]);

    $komunitas->update($validated);
    return response()->json(['data' => $komunitas]);
  }

  public function destroy($id)
  {
    $komunitas = Komunitas::findOrFail($id);
    $komunitas->delete();
    return response()->json(null, 204);
  }

  public function showRequests($id)
  {
    $komunitas = Komunitas::findOrFail($id);
    $requests = $komunitas->members()
      ->wherePivot('status', 'pending')
      ->get();

    return response()->json(['data' => $requests]);
  }

  public function handleRequest(Request $request, $id)
  {
    $validated = $request->validate([
      'user_id' => 'required|exists:users,id',
      'action' => 'required|in:accept,reject',
    ]);

    $komunitas = Komunitas::findOrFail($id);

    // Check if accepting would exceed member limit
    if ($validated['action'] === 'accept') {
      if ($komunitas->jumlah_anggota >= $komunitas->batas_anggota) {
        return response()->json([
          'message' => 'Cannot accept new member. Community has reached its member limit.',
        ], 422);
      }
    }

    $membership = $komunitas->members()
      ->where('user_id', $validated['user_id'])
      ->wherePivot('status', 'pending')
      ->firstOrFail();

    $newStatus = $validated['action'] === 'accept' ? 'accepted' : 'rejected';

    $komunitas->members()->updateExistingPivot($validated['user_id'], [
      'status' => $newStatus,
    ]);

    if ($newStatus === 'accepted') {
      $komunitas->increment('jumlah_anggota');
    }

    return response()->json(['message' => 'Request handled successfully']);
  }

  public function joinKomunitas($id)
  {
    $komunitas = Komunitas::findOrFail($id);

    // Check if community has reached its member limit
    if ($komunitas->jumlah_anggota >= $komunitas->batas_anggota) {
      return response()->json([
        'message' => 'Cannot join. Community has reached its member limit.',
      ], 422);
    }

    // Check if user is already a member or has a pending request
    $existingMembership = $komunitas->members()
      ->where('user_id', auth()->id())
      ->first();

    if ($existingMembership) {
      return response()->json([
        'message' => 'You already have a membership status in this community',
      ], 422);
    }

    // Create membership request
    $komunitas->members()->attach(auth()->id(), ['status' => 'pending']);

    return response()->json([
      'message' => 'Membership request sent successfully',
    ]);
  }

  public function leaveKomunitas($id)
  {
    $komunitas = Komunitas::findOrFail($id);

    // Cannot leave if you're the creator
    if ($komunitas->creator_id === auth()->id()) {
      return response()->json([
        'message' => 'Creator cannot leave the community',
      ], 422);
    }

    $membership = $komunitas->members()
      ->where('user_id', auth()->id())
      ->wherePivot('status', 'accepted')
      ->firstOrFail();

    $komunitas->members()->detach(auth()->id());
    $komunitas->decrement('jumlah_anggota');

    return response()->json([
      'message' => 'Successfully left the community',
    ]);
  }
}
