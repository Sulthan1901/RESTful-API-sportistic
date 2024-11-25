<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class BookingController extends Controller
{
  public function index(Request $request)
  {
    $query = Booking::query()->with(['court', 'user']);

    if (auth()->user()->role !== 'admin') {
      $query->where('user_id', auth()->id());
    }

    if ($request->has('status')) {
      $query->where('status', $request->status);
    }

    if ($request->has('start_date')) {
      $query->whereDate('start_time', '>=', $request->start_date);
    }

    if ($request->has('end_date')) {
      $query->whereDate('start_time', '<=', $request->end_date);
    }

    $bookings = $query->orderBy('start_time', 'desc')->paginate(10);

    return response()->json([
      'data' => $bookings->items(),
      'meta' => [
        'current_page' => $bookings->currentPage(),
        'last_page' => $bookings->lastPage(),
        'per_page' => $bookings->perPage(),
        'total' => $bookings->total(),
      ]
    ]);
  }

  public function checkAvailability(Request $request)
  {
    $validated = $request->validate([
      'court_id' => 'required|exists:courts,id',
      'start_time' => 'required|date|after:now',
      'duration' => 'required|integer|min:1|max:8',
    ]);

    $startTime = Carbon::parse($validated['start_time']);
    $endTime = $startTime->copy()->addHours($validated['duration']);

    $overlappingBookings = Booking::where('court_id', $validated['court_id'])
      ->where('status', 'confirmed')
      ->where(function ($query) use ($startTime, $endTime) {
        $query->whereBetween('start_time', [$startTime, $endTime])
          ->orWhereBetween(
            \DB::raw('DATE_ADD(start_time, INTERVAL duration HOUR)'),
            [$startTime, $endTime]
          );
      })->count();

    return response()->json([
      'data' => [
        'available' => $overlappingBookings === 0,
        'requested_time' => $startTime->toDateTimeString(),
        'end_time' => $endTime->toDateTimeString(),
      ]
    ]);
  }

  public function store(Request $request)
  {
    $validated = $request->validate([
      'court_id' => 'required|exists:courts,id',
      'start_time' => 'required|date|after:now',
      'duration' => 'required|integer|min:1|max:8',
      'notes' => 'nullable|string',
    ]);

    $startTime = Carbon::parse($validated['start_time']);
    $endTime = $startTime->copy()->addHours($validated['duration']);

    $overlappingBookings = Booking::where('court_id', $validated['court_id'])
      ->where('status', 'confirmed')
      ->where(function ($query) use ($startTime, $endTime) {
        $query->whereBetween('start_time', [$startTime, $endTime])
          ->orWhereBetween(
            \DB::raw('DATE_ADD(start_time, INTERVAL duration HOUR)'),
            [$startTime, $endTime]
          );
      })->exists();

    if ($overlappingBookings) {
      return response()->json([
        'message' => 'The court is not available for the selected time period'
      ], 422);
    }

    $booking = Booking::create([
      'user_id' => auth()->id(),
      'court_id' => $validated['court_id'],
      'start_time' => $validated['start_time'],
      'duration' => $validated['duration'],
      'notes' => $validated['notes'] ?? null,
      'status' => 'confirmed',
    ]);

    return response()->json(['data' => $booking->load('court')], 201);
  }

  public function destroy($bookingId)
  {
    $booking = Booking::where('id', $bookingId)
      ->where(function ($query) {
        $query->where('user_id', auth()->id())
          ->orWhere(function ($q) {
            $q->whereHas('user', function ($u) {
              $u->where('role', 'admin');
            });
          });
      })->firstOrFail();

    if (Carbon::parse($booking->start_time)->isPast()) {
      return response()->json([
        'message' => 'Cannot cancel a booking that has already started'
      ], 422);
    }

    $booking->delete();
    return response()->json([
      'message' => 'Berhasil menghapus booking'
    ], 200);
  }
}
