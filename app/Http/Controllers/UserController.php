<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class UserController extends Controller
{
    public function show(Request $request)
    // {
    //     $user = Auth::user();

    //     $cacheKey = 'current_user_' . $user->id;

    //     $userData = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($user) {
    //         $user = $request->user();
    //         return [
    //             'id' => $user->id,
    //             'name' => trim($user->first_name . ' ' . ($user->last_name ?? '')),
    //             'email' => $user->email,
    //             'phone' => $user->phone,
    //             'first_name' => $user->first_name,
    //             'last_name' => $user->last_name,
    //             'joined' => $user->created_at->format('F Y'),
    //             'profile_picture' => $user->profile_picture
    //                 ? asset('storage/' . $user->profile_picture)
    //                 : null,
    //         ];
    //     });

    //     if (!$user) {
    //         return response()->json(['message' => 'Unauthenticated'], 401);
    //     }
        
    //     return response()->json($userData);
    // }
    {
        $user = $request->user(); // Better: use $request->user() directly (Sanctum injects it)

        // Guard clause - return 401 early if not authenticated
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Now it's safe to use $user->id
        $cacheKey = 'current_user_' . $user->id;

        $userData = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($user) {
            return [
                'id' => $user->id,
                'name' => trim($user->first_name . ' ' . ($user->last_name ?? '')),
                'email' => $user->email,
                'phone' => $user->phone,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'joined' => $user->created_at->format('F Y'),
                'profile_picture' => $user->profile_picture
                    ? asset('storage/' . $user->profile_picture)
                    : null,
            ];
        });

        return response()->json($userData);
    }
}