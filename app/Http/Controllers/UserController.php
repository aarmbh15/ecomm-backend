<?php

// namespace App\Http\Controllers;

// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\Cache;

// class UserController extends Controller
// {
//     public function show(Request $request)
//     {
//         $user = $request->user(); // Better: use $request->user() directly (Sanctum injects it)

//         // Guard clause - return 401 early if not authenticated
//         if (!$user) {
//             return response()->json(['message' => 'Unauthenticated'], 401);
//         }

//         // Now it's safe to use $user->id
//         $cacheKey = 'current_user_' . $user->id;

//         $userData = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($user) {
//             return [
//                 'id' => $user->id,
//                 'name' => trim($user->first_name . ' ' . ($user->last_name ?? '')),
//                 'email' => $user->email,
//                 'phone' => $user->phone,
//                 'first_name' => $user->first_name,
//                 'last_name' => $user->last_name,
//                 'joined' => $user->created_at->format('F Y'),
//                 'profile_picture' => $user->profile_picture
//                     ? asset('storage/' . $user->profile_picture)
//                     : null,
//             ];
//         });

//         return response()->json($userData);
//     }
// }

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Get authenticated user profile data (cached)
     */
    public function show(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $cacheKey = 'current_user_' . $user->id;

        $userData = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($user) {
            return [
                'id'            => $user->id,
                'name'          => trim($user->first_name . ' ' . ($user->last_name ?? '')),
                'email'         => $user->email,
                'phone'         => $user->phone,
                'first_name'    => $user->first_name,
                'last_name'     => $user->last_name,
                'joined'        => $user->created_at->format('F Y'), // e.g. "December 2025"
                'profile_picture' => $user->photo // DB column is 'photo'
                    ? asset('storage/profile-photos/' . $user->photo)
                    : null,
            ];
        });

        return response()->json($userData);
    }

    /**
     * Update user profile (name, phone)
     */
    // public function update(Request $request)
    // {
    //     $user = $request->user();

    //     $validated = $request->validate([
    //         'first_name' => ['required', 'string', 'max:50'],
    //         'last_name'  => ['nullable', 'string', 'max:50'],
    //         'phone'      => ['nullable', 'string', 'max:20'],
    //     ]);

    //     $user->update($validated);

    //     // Clear cache to ensure fresh data on next /user call
    //     Cache::forget('current_user_' . $user->id);

    //     return response()->json([
    //         'message' => 'Profile updated successfully',
    //         'user'    => $this->getFreshUserData($user)
    //     ]);
    // }
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => ['sometimes', 'required', 'string', 'max:50'],
            'last_name'  => ['sometimes', 'nullable', 'string', 'max:50'],
            'phone'      => ['sometimes', 'nullable', 'string', 'max:20'],
        ]);

        $user->update($validated);

        // Clear cache
        Cache::forget('current_user_' . $user->id);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user'    => $this->getFreshUserData($user)
        ]);
    }

    /**
     * Update profile photo
     */
    public function updatePhoto(Request $request)
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'], // 2MB max
        ]);

        $user = $request->user();

        // Delete old photo if exists and not default
        if ($user->photo && $user->photo !== 'default.jpg') {
            Storage::disk('public')->delete('profile-photos/' . $user->photo);
        }

        // Store new photo
        $path = $request->file('photo')->store('profile-photos', 'public');
        $filename = basename($path);

        $user->update(['photo' => $filename]);

        // Clear cache
        Cache::forget('current_user_' . $user->id);

        return response()->json([
            'message' => 'Profile photo updated successfully',
            'profile_picture' => asset('storage/profile-photos/' . $filename)
        ]);
    }

    /**
     * Helper: Get fresh user data (used after updates)
     */
    private function getFreshUserData($user)
    {
        return [
            'id'              => $user->id,
            'name'            => trim($user->first_name . ' ' . ($user->last_name ?? '')),
            'email'           => $user->email,
            'phone'           => $user->phone,
            'first_name'      => $user->first_name,
            'last_name'       => $user->last_name,
            'joined'          => $user->created_at->format('F Y'),
            'profile_picture' => $user->photo
                ? asset('storage/profile-photos/' . $user->photo)
                : null,
        ];
    }
}