<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class KomunitasCreatorMiddleware
{
  public function handle(Request $request, Closure $next)
  {
    $komunitasId = $request->route('id');
    $komunitas = \App\Models\Komunitas::findOrFail($komunitasId);

    if (!auth()->check() || (auth()->id() !== $komunitas->creator_id && auth()->user()->role !== 'admin')) {
      return response()->json(['message' => 'Unauthorized. Only komunitas creator or admin can perform this action.'], 403);
    }


    return $next($request);
  }
}
