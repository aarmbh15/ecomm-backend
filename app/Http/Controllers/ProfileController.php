<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ProfileController extends Controller
{
    public function show(Request $request)
    // {
    //     $user = Auth::user();

    //     return response()->json([
    //         'id' => $user->id,
    //         'name' => $user->first_name . ' ' . $user->last_name,
    //         'email' => $user->email,
    //         'phone' => $user->phone ?? null,
    //         'joined' => $user->created_at->format('F Y'),
    //         'profile_picture' => $user->profile_picture ?? null, // if you store it
    //     ]);
    // }
    // {
    //     $user = Auth::user();

    //     // Cache the profile response for 10 minutes (adjust as needed)
    //     // This dramatically reduces database load for frequent visits
    //     $cacheKey = 'user_profile_' . $user->id;

    //     return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($user) {
    //         return response()->json([
    //             'id' => $user->id,
    //             'name' => $user->first_name . ' ' . ($user->last_name ?? ''),
    //             'email' => $user->email,
    //             'phone' => $user->phone,
    //             'joined' => $user->created_at->format('F Y'),
    //             'profile_picture' => $user->profile_picture 
    //                 ? asset('storage/' . $user->profile_picture) 
    //                 : null,
    //         ]);
    //     });
    // }
    {
        $user = Auth::user();

        $cacheKey = 'user_profile_' . $user->id;

        $profileData = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($user) {
            return [
                'id' => $user->id,
                'name' => $user->first_name . ' ' . ($user->last_name ?? ''),
                'email' => $user->email,
                'phone' => $user->phone,
                'joined' => $user->created_at->format('F Y'),
                'profile_picture' => $user->profile_picture 
                    ? asset('storage/' . $user->profile_picture) 
                    : null,
            ];
        });

        return response()->json($profileData);
    }
}
