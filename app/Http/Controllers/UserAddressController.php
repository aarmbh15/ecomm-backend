<?php

namespace App\Http\Controllers;

use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserAddressController extends Controller
{
    /**
     * List all addresses for the authenticated user
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $addresses = $user->addresses()
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $addresses
        ]);
    }

    /**
     * Store a new address
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'full_name'         => 'required|string|max:100',
            'phone'             => 'required|string|size:10|regex:/^[6-9]\d{9}$/', // Indian mobile
            'alternate_phone'   => 'nullable|string|size:10|regex:/^[6-9]\d{9}$/',
            'address_line_1'    => 'required|string|max:255',
            'address_line_2'    => 'nullable|string|max:255',
            'city'              => 'required|string|max:50',
            'state'             => 'required|string|max:50',
            'postal_code'       => 'required|string|size:6', // Indian PIN code
            'country'           => 'string|in:India', // or remove if allowing others
            'label'             => 'nullable|string|max:50',
            'type'              => ['required', Rule::in(['home', 'work', 'other'])],
            'is_default'        => 'boolean',
        ]);

        // Handle default logic
        if ($request->boolean('is_default')) {
            // Reset all others
            $user->addresses()->update(['is_default' => false]);
        } else {
            // If no default exists yet, make this one default
            $hasDefault = $user->addresses()->where('is_default', true)->exists();
            if (!$hasDefault) {
                $validated['is_default'] = true;
            }
        }

        $address = $user->addresses()->create($validated);

        return response()->json([
            'message' => 'Address added successfully',
            'address' => $address
        ], 201);
    }

    /**
     * Update an existing address
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();

        $address = $user->addresses()->findOrFail($id);

        $validated = $request->validate([
            'full_name'         => 'required|string|max:100',
            'phone'             => 'required|string|size:10|regex:/^[6-9]\d{9}$/',
            'alternate_phone'   => 'nullable|string|size:10|regex:/^[6-9]\d{9}$/',
            'address_line_1'    => 'required|string|max:255',
            'address_line_2'    => 'nullable|string|max:255',
            'city'              => 'required|string|max:50',
            'state'             => 'required|string|max:50',
            'postal_code'       => 'required|string|size:6',
            'country'           => 'string|in:India',
            'label'             => 'nullable|string|max:50',
            'type'              => ['required', Rule::in(['home', 'work', 'other'])],
            'is_default'        => 'boolean',
        ]);

        // Handle default logic
        if ($request->boolean('is_default') && !$address->is_default) {
            $user->addresses()->update(['is_default' => false]);
            $validated['is_default'] = true;
        } elseif (!$request->boolean('is_default') && $address->is_default) {
            // Trying to remove default status from the current default
            if ($user->addresses()->count() > 1) {
                // Allow only if there's another address to promote
                $validated['is_default'] = false;
                // Promote another address as default
                $user->addresses()->where('id', '!=', $id)->first()?->update(['is_default' => true]);
            } else {
                return response()->json([
                    'message' => 'Cannot remove default from your only address.'
                ], 422);
            }
        }

        $address->update($validated);

        return response()->json([
            'message' => 'Address updated successfully',
            'address' => $address->refresh()
        ]);
    }

    /**
     * Set an address as default (alternative endpoint)
     */
    public function setDefault(Request $request, $id)
    {
        $user = $request->user();
        $address = $user->addresses()->findOrFail($id);

        DB::transaction(function () use ($user, $address) {
            $user->addresses()->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });

        return response()->json([
            'message' => 'Default address updated successfully'
        ]);
    }

    /**
     * Delete an address
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $address = $user->addresses()->findOrFail($id);

        if ($address->is_default && $user->addresses()->count() === 1) {
            return response()->json([
                'message' => 'Cannot delete your only address. Please add another one first.'
            ], 422);
        }

        if ($address->is_default) {
            $nextDefault = $user->addresses()->where('id', '!=', $id)->first();
            if ($nextDefault) {
                $nextDefault->update(['is_default' => true]);
            }
        }

        $address->delete();

        return response()->json([
            'message' => 'Address deleted successfully'
        ]);
    }
}