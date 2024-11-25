<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Models\Booking;
use App\Models\Komunitas;
use App\Models\KomunitasMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Routing\Controller;

class CourtController extends Controller
{
    public function index(Request $request)
    {
        $query = Court::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        if ($request->has('available')) {
            $query->where('available', $request->boolean('available'));
        }

        $courts = $query->paginate(10);

        return response()->json(['data' => $courts]);
    }

    public function show($id)
    {
        $court = Court::findOrFail($id);
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
            'available' => 'boolean'
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
            'facilities' => 'nullable|array',
            'price_per_hour' => 'sometimes|numeric|min:0',
            'available' => 'sometimes|boolean'
        ]);

        $court->update($validated);

        return response()->json(['data' => $court]);
    }

    public function destroy($id)
    {
        $court = Court::findOrFail($id);
        $court->delete();

        return response()->json(['data' => true]);
    }
}

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $query = Booking::with(['court', 'user'])
            ->where('user_id', auth()->id());

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date')) {
            $date = Carbon::parse($request->date);
            $query->whereDate('start_time', $date);
        }

        $bookings = $query->orderBy('start_time', 'desc')->paginate(10);

        return response()->json(['data' => $bookings]);
    }

    public function checkAvailability(Request $request)
    {
        $validated = $request->validate([
            'court_id' => 'required|exists:courts,id',
            'start_time' => 'required|date|after:now',
            'duration' => 'required|integer|min:1|max:8'
        ]);

        $startTime = Carbon::parse($validated['start_time']);
        $endTime = $startTime->copy()->addHours($validated['duration']);

        $conflictingBookings = Booking::where('court_id', $validated['court_id'])
            ->where('status', 'confirmed')
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween(
                        DB::raw('DATE_ADD(start_time, INTERVAL duration HOUR)'),
                        [$startTime, $endTime]
                    );
            })
            ->exists();

        return response()->json([
            'data' => [
                'available' => !$conflictingBookings,
                'start_time' => $startTime,
                'end_time' => $endTime
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'court_id' => 'required|exists:courts,id',
            'start_time' => 'required|date|after:now',
            'duration' => 'required|integer|min:1|max:8',
            'notes' => 'nullable|string'
        ]);

        // Check court availability
        $startTime = Carbon::parse($validated['start_time']);
        $endTime = $startTime->copy()->addHours($validated['duration']);

        $conflictingBookings = Booking::where('court_id', $validated['court_id'])
            ->where('status', 'confirmed')
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween(
                        DB::raw('DATE_ADD(start_time, INTERVAL duration HOUR)'),
                        [$startTime, $endTime]
                    );
            })
            ->exists();

        if ($conflictingBookings) {
            return response()->json([
                'message' => 'Court is not available for the selected time period'
            ], 422);
        }

        $booking = Booking::create([
            'user_id' => auth()->id(),
            'court_id' => $validated['court_id'],
            'start_time' => $validated['start_time'],
            'duration' => $validated['duration'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'confirmed'
        ]);

        return response()->json(['data' => $booking], 201);
    }

    public function destroy($bookingId)
    {
        $booking = Booking::where('id', $bookingId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if (
            $booking->status === 'confirmed' &&
            Carbon::parse($booking->start_time)->isPast()
        ) {
            return response()->json([
                'message' => 'Cannot cancel past bookings'
            ], 422);
        }

        $booking->status = 'cancelled';
        $booking->save();

        return response()->json(['data' => true]);
    }
}

class KomunitasController extends Controller
{
    public function index(Request $request)
    {
        $query = Komunitas::with('creator');

        if ($request->has('search')) {
            $query->where('nama_komunitas', 'like', '%' . $request->search . '%')
                ->orWhere('deskripsi_komunitas', 'like', '%' . $request->search . '%');
        }

        $komunitas = $query->paginate(10);

        return response()->json(['data' => $komunitas]);
    }

    public function show($id)
    {
        $komunitas = Komunitas::with(['creator', 'members' => function ($query) {
            $query->where('status', 'accepted');
        }])->findOrFail($id);

        return response()->json(['data' => $komunitas]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_komunitas' => 'required|string|max:255|unique:komunitas',
            'deskripsi_komunitas' => 'required|string'
        ]);

        $komunitas = Komunitas::create([
            'nama_komunitas' => $validated['nama_komunitas'],
            'deskripsi_komunitas' => $validated['deskripsi_komunitas'],
            'creator_id' => auth()->id()
        ]);

        // Auto-join creator as member
        $komunitas->members()->attach(auth()->id(), ['status' => 'accepted']);
        $komunitas->increment('jumlah_anggota');

        return response()->json(['data' => $komunitas], 201);
    }

    public function update(Request $request, $id)
    {
        $komunitas = Komunitas::findOrFail($id);

        $validated = $request->validate([
            'nama_komunitas' => 'sometimes|string|max:255|unique:komunitas,nama_komunitas,' . $id,
            'deskripsi_komunitas' => 'sometimes|string'
        ]);

        $komunitas->update($validated);

        return response()->json(['data' => $komunitas]);
    }

    public function destroy($id)
    {
        $komunitas = Komunitas::findOrFail($id);
        $komunitas->delete();

        return response()->json(['data' => true]);
    }

    public function showRequests($id)
    {
        $komunitas = Komunitas::findOrFail($id);

        $requests = $komunitas->members()
            ->where('status', 'pending')
            ->with('pivot')
            ->get();

        return response()->json(['data' => $requests]);
    }

    public function handleRequest(Request $request, $id)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'action' => 'required|in:accept,reject'
        ]);

        $komunitas = Komunitas::findOrFail($id);

        $membership = $komunitas->members()
            ->wherePivot('user_id', $validated['user_id'])
            ->wherePivot('status', 'pending')
            ->firstOrFail();

        if ($validated['action'] === 'accept') {
            $komunitas->members()
                ->updateExistingPivot($validated['user_id'], ['status' => 'accepted']);
            $komunitas->increment('jumlah_anggota');
        } else {
            $komunitas->members()
                ->updateExistingPivot($validated['user_id'], ['status' => 'rejected']);
        }

        return response()->json(['data' => true]);
    }
}
