<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Mail\WelcomeMail;
use Illuminate\Support\Facades\Log;

// class AuthController extends Controller
// {
//     public function register(Request $request)
//     {
//         // $start = microtime(true); // Start time
        
//         // Step 1: Validate input fields
//         $request->validate([
//             'first_name' => 'required|string|max:255',
//             'last_name' => 'required|string|max:255',
//             'email' => 'required|email|unique:users,email',
//         ]);

//         // Step 2: Auto-generate a random password
//         $generatedPassword = Str::random(8); // You can adjust the length of the password

//         function generatePassword($length = 8)
//         {
//             $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#';
//             return substr(str_shuffle(str_repeat($characters, $length)), 0, $length);
//         }

//         $generatedPassword = generatePassword(8);

//         // Step 3: Hash the password before saving it to the database
//         $hashedPassword = Hash::make($generatedPassword);

//         // Step 4: Create the user with the auto-generated password
//         // $user = User::registerNewUser($request->first_name, $request->last_name, $request->email, $hashedPassword);
//         $user = User::create([
//             'first_name' => $request->first_name,
//             'last_name'  => $request->last_name,
//             'email'      => $request->email,
//             'password'   => $hashedPassword, // will be hashed automatically
//         ]);

//         // Step 5: Send an email with the generated password
//         Mail::to($request->email)->send(new WelcomeMail($request->first_name, $request->last_name, $request->email, $generatedPassword));

//         // $end = microtime(true); // End time
//         // Log::info('registerUser process took: ' . ($end - $start) . ' seconds');

//         // Step 6: Redirect with success message
//         return response()->json([
//             'message' => 'Account created successfully! Password sent to email.'
//         ], 201);

//     }

//      public function login(Request $request)
//     // {
//     //     $request->validate([
//     //         'email' => 'required|email',
//     //         'password' => 'required|string',
//     //     ]);

//     //     $user = User::where('email', $request->email)->first();

//     //     if (!$user || !Hash::check($request->password, $user->password)) {
//     //         return response()->json(['message' => 'Invalid credentials'], 401);
//     //     }

//     //     // Create token
//     //     $token = $user->createToken('api-token')->plainTextToken;

//     //     return response()->json([
//     //         'message' => 'Login successful',
//     //         'user' => $user,
//     //         'token' => $token
//     //     ], 200);
//     // }

//     {
//         $request->validate([
//             'email' => 'required|email',
//             'password' => 'required|string',
//         ]);

//         $credentials = $request->only('email', 'password');

//         if (Auth::attempt($credentials)) {
//             // This is crucial: regenerate session to prevent session fixation
//             $request->session()->regenerate();

//             return response()->json([
//                 'message' => 'Login successful',
//                 'user' => Auth::user(),
//             ], 200);
//         }

//         return response()->json([
//             'message' => 'Invalid credentials',
//         ], 401);
//     }

//     // public function logout(Request $request)
//     // {
//     //     Auth::logout();
//     //     $request->session()->invalidate();
//     //     $request->session()->regenerateToken();

//     //     return response()->json([
//     //         'message' => 'Logged out successfully',
//     //     ]);
//     // }
// }

class AuthController extends Controller
{
    // Your existing register method (unchanged)
    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
        ]);

        $generatedPassword = $this->generatePassword(8);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'email'      => $request->email,
            'password'   => Hash::make($generatedPassword),
        ]);

        Mail::to($request->email)->send(new WelcomeMail(
            $request->first_name,
            $request->last_name,
            $request->email,
            $generatedPassword
        ));

        return response()->json([
            'message' => 'Account created successfully! Password sent to email.'
        ], 201);
    }

    private function generatePassword($length = 8)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#';
        return substr(str_shuffle(str_repeat($characters, $length)), 0, $length);
    }

    // NEW: Session-based login (no token creation)
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials)) {
            // Regenerate session to prevent session fixation attacks
            // $request->session()->regenerate();

            return response()->json([
                'message' => 'Logged in successfully',
                'user'    => Auth::user(),
            ]);
        }

        return response()->json([
            'message' => 'Invalid credentials'
        ], 401);
    }

    // NEW: Proper session logout
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}
