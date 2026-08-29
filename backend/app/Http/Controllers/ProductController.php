<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductCollection;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): ProductCollection
    {
        $perPage = min(max($request->integer('per_page', 12), 1), 100);

        return new ProductCollection(
            Product::latest()->paginate($perPage)
        );
    }
}
