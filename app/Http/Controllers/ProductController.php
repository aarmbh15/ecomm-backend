<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * List products with filters for the collection page
     */
    public function index(Request $request)
    {
        $query = Product::where('is_active', true)
            ->with(['primaryImage', 'category']);

        // Gender mapping for flexible input
        $genderMap = [
            'men'    => 'men',
            'male'   => 'men',
            'women'  => 'women',
            'female' => 'women',
        ];

        $selectedGenderSlug = null;
        if ($request->has('gender')) {
            $genderInput = strtolower($request->input('gender'));
            $selectedGenderSlug = $genderMap[$genderInput] ?? null;

            if (!$selectedGenderSlug) {
                // Invalid gender → return no products
                $query->whereRaw('1 = 0');
            } else {
                // Filter products under the top-level gender category (Men or Women)
                $query->whereHas('category.parent', function ($q) use ($selectedGenderSlug) {
                    $q->where('slug', $selectedGenderSlug)
                    ->whereNull('parent_id');
                });
            }
        }

        // Subcategory filter (e.g., 'tshirts', 'hoodies', 'dresses')
        if ($request->has('category')) {
            $subSlug = $request->input('category');

            $query->whereHas('category', function ($q) use ($subSlug) {
                // Match slugs like "men-tshirts" or "women-tshirts"
                $q->where('slug', 'LIKE', "%-{$subSlug}");
            });

            // If gender is also selected, ensure the subcategory belongs to that gender
            if ($selectedGenderSlug) {
                $query->whereHas('category.parent', function ($q) use ($selectedGenderSlug) {
                    $q->where('slug', $selectedGenderSlug);
                });
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = strtolower($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                ->orWhereRaw('LOWER(description) LIKE ?', ["%{$search}%"])
                ->orWhereHas('category', function ($cat) use ($search) {
                    $cat->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
                });
            });
        }

        // Price range
        if ($request->has('min_price') && $request->input('min_price') > 0) {
            $min = $request->input('min_price');
            $query->where(function ($q) use ($min) {
                $q->whereNull('sale_price')->where('base_price', '>=', $min)
                ->orWhere('sale_price', '>=', $min);
            });
        }

        if ($request->has('max_price')) {
            $max = $request->input('max_price');
            $query->where(function ($q) use ($max) {
                $q->whereNull('sale_price')->where('base_price', '<=', $max)
                ->orWhere('sale_price', '<=', $max);
            });
        }

        // Sorting
        $sortBy = $request->input('sort', 'featured');
        switch ($sortBy) {
            case 'price-low':
                $query->orderByRaw('COALESCE(sale_price, base_price) ASC');
                break;
            case 'price-high':
                $query->orderByRaw('COALESCE(sale_price, base_price) DESC');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            default: // featured
                $query->orderBy('is_featured', 'desc')
                    ->orderBy('created_at', 'desc');
                break;
        }

        $products = $query->paginate(20);

        $mapped = $products->map(function ($product) {
            $imageUrl = $product->primaryImage
                ? asset('storage/' . $product->primaryImage->path)
                : asset('placeholder.jpg');

            return [
                'id'       => $product->id,
                'title'    => $product->name,
                'category' => $product->category?->name ?? 'Uncategorized',
                'price'    => $product->sale_price ?? $product->base_price,
                'image'    => $imageUrl,
            ];
        });

        return response()->json([
            'products'      => $mapped->values(),
            'total'         => $products->total(),
            'current_page'  => $products->currentPage(),
            'last_page'     => $products->lastPage(),
        ]);
    }

    /**
     * Real-time search for homepage dropdown
     */
    public function search(Request $request)
    {
        $query = $request->input('q', '');

        if (empty(trim($query))) {
            return response()->json([]);
        }

        $search = strtolower(trim($query));

        $products = Product::where('is_active', true)
            ->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                  ->orWhereHas('category', function ($cat) use ($search) {
                      $cat->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
                  });
            })
            ->with(['primaryImage'])
            ->limit(10)
            ->get()
            ->map(function ($product) {
                $imageUrl = $product->primaryImage
                    ? asset('storage/' . $product->primaryImage->path)
                    : asset('placeholder.jpg'); // Put a placeholder.jpg in public/

                return [
                    'id' => $product->id,
                    'title' => $product->name,
                    'category' => $product->category?->name ?? 'Uncategorized',
                    'price' => $product->sale_price ?? $product->base_price,
                    'image' => $imageUrl,
                ];
            });

        return response()->json($products);
    }

    /**
     * Featured products for homepage (6 items)
     */
    public function featured()
    {
        $products = Product::where('is_featured', true)
            ->where('is_active', true)
            ->with(['primaryImage'])
            ->limit(6)
            ->get()
            ->map(function ($product) {
                $imageUrl = $product->primaryImage
                    ? asset('storage/' . $product->primaryImage->path)
                    : asset('placeholder.jpg');

                return [
                    'id' => $product->id,
                    'title' => $product->name,
                    'category' => $product->category?->name ?? 'Uncategorized',
                    'price' => $product->sale_price ?? $product->base_price,
                    'image' => $imageUrl,
                ];
            });

        return response()->json($products);
    }

    //1st version
    // public function show($id)
    // {
    //     $product = Product::where('is_active', true)
    //         ->with(['primaryImage', 'category'])
    //         ->findOrFail($id);

    //     // You can expand this later with variants, sizes, colors, reviews, etc.
    //     return response()->json([
    //         'id' => $product->id,
    //         'title' => $product->name,
    //         'description' => $product->description,
    //         'short_description' => $product->short_description,
    //         'price' => $product->sale_price ?? $product->base_price,
    //         'base_price' => $product->base_price,
    //         'sale_price' => $product->sale_price,
    //         'category' => $product->category?->name,
    //         'image' => $product->primaryImage
    //             ? asset('storage/' . $product->primaryImage->path)
    //             : asset('placeholder.jpg'),
    //         'is_customizable' => $product->is_customizable,
    //         // Add more fields later: sizes, colors, stock, etc.
    //     ]);
    // }

    //2nd version
    // public function show($id)
    // {
    //     $product = Product::with([
    //         'category',
    //         'primaryImage',
    //         'images',
    //         'variants' => function ($q) {
    //             $q->active()->inStock()->with(['primaryImage', 'images']);
    //         }
    //     ])
    //     ->where('is_active', true)
    //     ->findOrFail($id);

    //     $primaryImageUrl = $product->primaryImage
    //         ? asset('storage/' . $product->primaryImage->path)
    //         : asset('placeholder.jpg');

    //     // All product-level images + prepend primary
    //     $allImages = $product->images->pluck('path')
    //         ->map(fn($path) => asset('storage/' . $path))
    //         ->prepend($primaryImageUrl)
    //         ->unique()
    //         ->values()
    //         ->all();

    //     // Prepare variants data for frontend
    //     $variants = $product->variants->map(function ($variant) {
    //         $variantImages = $variant->images->pluck('path')
    //             ->map(fn($path) => asset('storage/' . $path))
    //             ->prepend(
    //                 $variant->primaryImage
    //                     ? asset('storage/' . $variant->primaryImage->path)
    //                     : null
    //             )
    //             ->filter()
    //             ->unique()
    //             ->values()
    //             ->all();

    //         return [
    //             'id' => $variant->id,
    //             'sku' => $variant->sku,
    //             'price' => $variant->sale_price ?? $variant->price,
    //             'stock_quantity' => $variant->stock_quantity,
    //             'attributes' => $variant->attributes, // e.g. ["size" => "M", "color" => "Black"]
    //             'images' => $variantImages,
    //         ];
    //     })->values()->all();

    //     return response()->json([
    //         'id' => $product->id,
    //         'title' => $product->name,
    //         'slug' => $product->slug,
    //         'description' => $product->description,
    //         'short_description' => $product->short_description ?? '',
    //         'base_price' => $product->base_price,
    //         'sale_price' => $product->sale_price,
    //         'display_price' => $product->sale_price ?? $product->base_price,
    //         'category' => $product->category?->name ?? 'Uncategorized',
    //         'image' => $primaryImageUrl,              // main display image
    //         'images' => $allImages,                   // gallery thumbnails
    //         'variants' => $variants,
    //         'is_customizable' => $product->is_customizable,
    //     ]);
    // }

    //3rd version
    public function show($id)
    {
        $product = Product::with([
            'category',
            'primaryImage',
            'images',
            'variants' => function ($q) {
                $q->active()->inStock()->with(['primaryImage', 'images']);
            }
        ])
        ->where('is_active', true)
        ->findOrFail($id);

        // === RELATED PRODUCTS ===
        // $related = Product::where('category_id', $product->category_id)
        //     ->where('id', '!=', $product->id)
        //     ->where('is_active', true)
        //     ->with(['primaryImage'])
        //     ->inRandomOrder()
        //     ->limit(8)
        //     ->get();

        // Fallback if not enough in same category
        // if ($related->count() < 6) {
        //     $extra = Product::where('is_active', true)
        //         ->where('id', '!=', $product->id)
        //         ->where('id', '!=', $related->pluck('id'))
        //         ->where('is_featured', true) // prefer featured first
        //         ->orWhere('is_featured', false)
        //         ->with(['primaryImage'])
        //         ->inRandomOrder()
        //         ->limit(6 - $related->count())
        //         ->get();

        //     $related = $related->merge($extra);
        // }

        // $relatedProducts = $related->map(function ($rel) {
        //     $imageUrl = $rel->primaryImage
        //         ? asset('storage/' . $rel->primaryImage->path)
        //         : asset('placeholder.jpg');

        //     return [
        //         'id' => $rel->id,
        //         'title' => $rel->name,
        //         'price' => $rel->sale_price ?? $rel->base_price,
        //         'image' => $imageUrl,
        //     ];
        // })->take(6);

        // === RELATED PRODUCTS ===
        $relatedQuery = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->with(['primaryImage']);

        $related = (clone $relatedQuery)->inRandomOrder()->limit(8)->get();

        $relatedCount = $related->count();

        // If we have less than 6 from same category, fill with others
        if ($relatedCount < 6) {
            $excludedIds = $related->pluck('id')->push($product->id)->unique();

            $extra = Product::where('is_active', true)
                ->whereNotIn('id', $excludedIds)
                ->whereHas('category') // ensure it has a category
                ->with(['primaryImage'])
                ->inRandomOrder()
                ->limit(6 - $relatedCount)
                ->get();

            $related = $related->merge($extra);
        }

        $relatedProducts = $related->take(6)->map(function ($rel) {
            $imageUrl = $rel->primaryImage
                ? asset('storage/' . $rel->primaryImage->path)
                : asset('placeholder.jpg');

            return [
                'id' => $rel->id,
                'title' => $rel->name,
                'price' => $rel->sale_price ?? $rel->base_price,
                'image' => $imageUrl,
            ];
        });

        // === EXISTING PRODUCT DATA ===
        $primaryImageUrl = $product->primaryImage
            ? asset('storage/' . $product->primaryImage->path)
            : asset('placeholder.jpg');

        $allImages = $product->images->pluck('path')
            ->map(fn($path) => asset('storage/' . $path))
            ->prepend($primaryImageUrl)
            ->unique()
            ->values()
            ->all();

        $variants = $product->variants->map(function ($variant) {
            $variantImages = $variant->images->pluck('path')
                ->map(fn($path) => asset('storage/' . $path))
                ->prepend(
                    $variant->primaryImage
                        ? asset('storage/' . $variant->primaryImage->path)
                        : null
                )
                ->filter()
                ->unique()
                ->values()
                ->all();

            return [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'price' => $variant->sale_price ?? $variant->price,
                'stock_quantity' => $variant->stock_quantity,
                'attributes' => $variant->attributes,
                'images' => $variantImages,
            ];
        })->values()->all();

        return response()->json([
            'id' => $product->id,
            'title' => $product->name,
            'slug' => $product->slug,
            'description' => $product->description,
            'short_description' => $product->short_description ?? '',
            'base_price' => $product->base_price,
            'sale_price' => $product->sale_price,
            'display_price' => $product->sale_price ?? $product->base_price,
            'category' => $product->category?->name ?? 'Uncategorized',
            'image' => $primaryImageUrl,
            'images' => $allImages,
            'variants' => $variants,
            'is_customizable' => $product->is_customizable,

            // NEW: Related products
            'related_products' => $relatedProducts,
        ]);
    }
}