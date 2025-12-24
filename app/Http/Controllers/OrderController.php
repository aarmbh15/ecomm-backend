<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $orders = $user->orders()
            ->with(['items', 'address'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $orders
        ]);
    }
}