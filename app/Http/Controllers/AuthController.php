<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
  public function register(Request $request)
  {
    $validated = $request->validate([
      'username' => 'required|string|min:6|unique:users',
      'email' => 'required|email|unique:users',
      'password' => ['required', 'confirmed', Password::min(8)],
    ]);

    $user = User::create([
      'username' => $validated['username'],
      'email' => $validated['email'],
      'password' => Hash::make($validated['password']),
    ]);

    return response()->json([
      'data' => [
        'id' => $user->id,
        'username' => $user->username,
        'email' => $user->email,
        'role' => $user->role,
      ]
    ], 201);
  }

  public function login(Request $request)
  {
    $validated = $request->validate([
      'email' => 'required|email',
      'password' => 'required',
    ]);

    if (!auth()->attempt($validated)) {
      return response()->json(['message' => 'Invalid credentials'], 401);
    }

    $user = auth()->user();
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
      'data' => [
        'id' => $user->id,
        'username' => $user->username,
        'email' => $user->email,
        'role' => $user->role,
        'token' => $token,
      ]
    ]);
  }

  public function logout(Request $request)
  {
    $request->user()->currentAccessToken()->delete();
    return response()->json(['data' => true]);
  }

  public function currentUser(Request $request)
  {
    return response()->json([
      'data' => [
        'id' => $request->user()->id,
        'username' => $request->user()->username,
        'email' => $request->user()->email,
        'role' => $request->user()->role,
      ]
    ]);
  }

  public function updateCurrentUser(Request $request)
  {
    $validated = $request->validate([
      'username' => 'sometimes|string|min:6|unique:users,username,' . auth()->id(),
      'password' => ['sometimes', Password::min(8)],
    ]);

    $user = $request->user();

    if (isset($validated['username'])) {
      $user->username = $validated['username'];
    }

    if (isset($validated['password'])) {
      $user->password = Hash::make($validated['password']);
    }

    $user->save();

    return response()->json([
      'data' => [
        'id' => $user->id,
        'username' => $user->username,
        'email' => $user->email,
      ]
    ]);
  }
}
