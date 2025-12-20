<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth:sanctum'); // Only logged-in users
    // }

    public function count()
    {
        $count = Cart::where('user_id', Auth::id())
                     ->sum('quantity');

        return response()->json(['count' => (int) $count]);
    }

    // public function add(Request $request)
    // {
    //     $request->validate([
    //         'product_id' => 'required|exists:products,id',
    //         'quantity' => 'integer|min:1|default:1',
    //     ]);

    //     $userId = Auth::id();
    //     $productId = $request->product_id;
    //     $quantity = $request->quantity;

    //     $cartItem = Cart::updateOrCreate(
    //         [
    //             'user_id' => $userId,
    //             'product_id' => $productId,
    //             'variant_id' => null, // adjust later
    //         ],
    //         [
    //             'quantity' => \DB::raw("quantity + {$quantity}"),
    //         ]
    //     );

    //     return response()->json(['message' => 'Added to cart', 'cartItem' => $cartItem]);
    // }
    // public function add(Request $request)
    // {
    //     $request->validate([
    //         'product_id' => 'required|exists:products,id',
    //         'quantity'   => 'integer|min:1',
    //         'variant_id' => 'nullable|exists:product_variants,id',
    //     ]);

    //     $userId     = Auth::id();
    //     $productId  = $request->product_id;
    //     $variantId  = $request->variant_id ?? null;
    //     $addQty     = $request->quantity ?? 1;

    //     // Find existing cart item
    //     $cartItem = Cart::where([
    //         'user_id'    => $userId,
    //         'product_id' => $productId,
    //         'variant_id' => $variantId,
    //     ])->first();

    //     if ($cartItem) {
    //         // Update existing: just increment quantity
    //         $cartItem->increment('quantity', $addQty);
    //     } else {
    //         // Create new
    //         $cartItem = Cart::create([
    //             'user_id'    => $userId,
    //             'product_id' => $productId,
    //             'variant_id' => $variantId,
    //             'quantity'   => $addQty,
    //         ]);
    //     }

    //     return response()->json([
    //         'message'  => 'Added to cart successfully',
    //         'cartItem' => $cartItem->load('product', 'variant'),
    //     ]);
    // }
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
            'variant_id' => 'nullable|exists:product_variants,id',
        ]);

        $userId     = Auth::id();
        $productId  = $request->product_id;
        $variantId  = $request->variant_id;
        $addQty     = $request->quantity;

        // Start transaction to ensure atomicity
        \DB::transaction(function () use ($userId, $productId, $variantId, $addQty, &$cartItem) {
            // Lock the variant row to prevent race conditions
            $variant = null;
            if ($variantId) {
                $variant = \App\Models\ProductVariant::where('id', $variantId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($variant->stock_quantity < $addQty) {
                    throw new \Exception('Not enough stock available');
                }

                // Decrease stock
                $variant->decrement('stock_quantity', $addQty);
            }

            // Now handle cart
            $cartItem = Cart::where([
                'user_id'    => $userId,
                'product_id' => $productId,
                'variant_id' => $variantId ?? null,
            ])->first();

            if ($cartItem) {
                $cartItem->increment('quantity', $addQty);
            } else {
                $cartItem = Cart::create([
                    'user_id'    => $userId,
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'quantity'   => $addQty,
                ]);
            }
        });

        return response()->json([
            'message' => 'Added to cart successfully',
        ]);
    }
    
    // public function index()
    // {
    //     $items = Cart::with(['product', 'variant'])
    //         ->where('user_id', Auth::id())
    //         ->get();

    //     $cartData = $items->map(function ($item) {
    //         return [
    //             'id' => $item->id, // cart row id
    //             'product_id' => $item->product_id,
    //             'title' => $item->product->title,
    //             'image' => $item->product->image_url ?? '/placeholder.jpg', // adjust field name
    //             'category' => $item->product->category ?? 'Uncategorized',
    //             'price' => $item->price, // uses your accessor
    //             'quantity' => $item->quantity,
    //         ];
    //     });

    //     $totalPrice = $items->sum('subtotal');

    //     return response()->json([
    //         'cart' => $cartData,
    //         'totalPrice' => $totalPrice,
    //         'totalItems' => $items->sum('quantity'),
    //     ]);
    // }

    // public function index()
    // {
    //     try {
    //         $items = Cart::with(['product', 'variant'])
    //             ->where('user_id', Auth::id())
    //             ->get();

    //         $cartData = $items->map(function ($cartItem) {
    //             $product = $cartItem->product;
    //             $variant = $cartItem->variant;

    //             // Safely get variant details (color • size)
    //             $variantDetails = null;
    //             if ($variant) {
    //                 $attrs = $variant->attributes;
    //                 // If attributes is JSON string (from DB), decode it
    //                 if (is_string($attrs)) {
    //                     $attrs = json_decode($attrs, true);
    //                 }
    //                 // If it's already array or object, convert
    //                 if (is_array($attrs)) {
    //                     $variantDetails = collect($attrs)->values()->join(' • ');
    //                 }
    //             }

    //             // Safely get category name
    //             $categoryName = null;
    //             if ($product && $product->category) {
    //                 $categoryName = $product->category->name ?? 'Uncategorized';
    //             }

    //             return [
    //                 'id'              => $cartItem->id,
    //                 'title'           => $product?->title ?? 'Unknown Product',
    //                 'variant_details' => $variantDetails,                    // e.g., "Navy • L"
    //                 'image'           => $product?->image_url ?? '/placeholder.jpg',
    //                 'category'        => (object)[ 'name' => $categoryName ], // matches frontend expectation
    //                 'price'           => $cartItem->price,
    //                 'quantity'        => $cartItem->quantity,
    //             ];
    //         });

    //         return response()->json([
    //             'cart'       => $cartData,
    //             'totalPrice' => $items->sum('subtotal'),
    //             'totalItems' => $items->sum('quantity'),
    //         ]);
    //     } catch (\Exception $e) {
    //         \Log::error('Cart index error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
    //         return response()->json(['error' => 'Failed to load cart'], 500);
    //     }
    // }
    public function index()
    {
        try {
            $items = Cart::with(['product.category', 'variant'])
                ->where('user_id', Auth::id())
                ->get();

            $cartData = $items->map(function ($cartItem) {
                // Safely get product (force load if missing)
                $product = $cartItem->product;
                if (!$product) {
                    // Fallback: reload if relationship failed
                    $product = \App\Models\Product::find($cartItem->product_id);
                }

                $title = $product?->name ?? 'Unknown Product';

                $variant = $cartItem->variant;

                // Variant details: "Navy • L"
                $variantDetails = null;
                if ($variant && $variant->attributes) {
                    $attrs = is_string($variant->attributes) 
                        ? json_decode($variant->attributes, true) 
                        : $variant->attributes;

                    if (is_array($attrs)) {
                        $variantDetails = collect($attrs)->values()->join(' • ');
                    }
                }

                // Image priority: variant image → product image → placeholder
                $image = $product?->image_url ?? 'https://via.placeholder.com/150';

                // Category name
                $categoryName = $product?->category?->name ?? 'Uncategorized';

                return [
                    'id'              => $cartItem->id,
                    'title'           => $title,
                    'variant_details' => $variantDetails,
                    'image'           => $image,
                    'category'        => (object)['name' => $categoryName],
                    'price'           => $cartItem->price,
                    'quantity'        => $cartItem->quantity,
                ];
            });

            return response()->json([
                'cart'       => $cartData,
                'totalPrice' => $items->sum('subtotal'),
                'totalItems' => $items->sum('quantity'),
            ]);
        } catch (\Exception $e) {
            \Log::error('Cart index error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load cart'], 500);
        }
    }

    // public function remove($id)
    // {
    //     Cart::where('id', $id)->where('user_id', Auth::id())->delete();
    //     return response()->json(['message' => 'Removed']);
    // }
    public function remove($id)
    {
        \DB::transaction(function () use ($id) {
            $cartItem = Cart::where('id', $id)->where('user_id', Auth::id())->firstOrFail();

            // Return stock
            if ($cartItem->variant_id) {
                \App\Models\ProductVariant::where('id', $cartItem->variant_id)
                    ->increment('stock_quantity', $cartItem->quantity);
            }

            $cartItem->delete();
        });

        return response()->json(['message' => 'Removed from cart']);
    }

    // public function updateQuantity(Request $request, $id)
    // {
    //     $request->validate(['quantity' => 'required|integer|min:1']);

    //     $item = Cart::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
    //     $item->quantity = $request->quantity;
    //     $item->save();

    //     return response()->json(['message' => 'Quantity updated']);
    // }
    public function updateQuantity(Request $request, $id)
    {
        $request->validate(['quantity' => 'required|integer|min:0']);

        \DB::transaction(function () use ($request, $id) {
            $cartItem = Cart::where('id', $id)->where('user_id', Auth::id())->firstOrFail();

            $oldQty = $cartItem->quantity;
            $newQty = $request->quantity;

            $diff = $oldQty - $newQty; // positive = stock to return

            if ($diff > 0 && $cartItem->variant_id) {
                \App\Models\ProductVariant::where('id', $cartItem->variant_id)
                    ->increment('stock_quantity', $diff);
            } elseif ($diff < 0 && $cartItem->variant_id) {
                // User increased quantity — check stock
                $variant = \App\Models\ProductVariant::where('id', $cartItem->variant_id)->first();
                if ($variant->stock_quantity < abs($diff)) {
                    throw new \Exception('Not enough stock');
                }
                $variant->decrement('stock_quantity', abs($diff));
            }

            if ($newQty <= 0) {
                $cartItem->delete();
            } else {
                $cartItem->quantity = $newQty;
                $cartItem->save();
            }
        });

        return response()->json(['message' => 'Quantity updated']);
    }
}