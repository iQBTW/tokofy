<?php

namespace App\Http\Controllers\Dashboard;

use Inertia\Inertia;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $products = Product::with('product_images')
            ->select(
                'products.*',
            )
            ->when($request->q, function ($query, $q) {
                $query->where('products.name', 'like', '%' . $q . '%');
            })->paginate(10);

        foreach ($products as $product) {
            $product->price = currencyFormat($product->price);
        }

        return Inertia::render('Admin/Product/Index', [
            'products' => $products,
            'search' => $request->only('q'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/Product/Create', [
            'categories' => Category::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|unique:products,name|max:255',
            'stock' => 'required|numeric',
            'price' => 'required|numeric',
            'category_id' => 'required|exists:categories,id',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        ]);

        DB::beginTransaction();

        try {
            $product = Product::create([
                'name' => $validated['name'],
                'stock' => $validated['stock'],
                'price' => $validated['price'],
                'category_id' => $validated['category_id'],
            ]);

            // Proses gambar-gambar yang diupload
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $imageFile) {
                    $fileName = time() . '_' . $imageFile->getClientOriginalName();
                    $path = $imageFile->storeAs('img/product', $fileName, 'public');

                    // Buat entri baru untuk setiap gambar
                    ProductImage::create([
                        'image' => $path,
                        'product_id' => $product->id,
                    ]);
                }
            }

            DB::commit();

            return redirect(route('product.index'))->with('success', 'Product created successfully');
        }
        catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $product = Product::with('product_images')->findOrFail($id);

        // return $product;
        $product->price = number_format($product->price, 0, '.', '');

        return Inertia::render('Admin/Product/Edit', [
            'categories' => Category::all(),
            'product' => $product,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'stock' => 'required|numeric',
            'price' => 'required|numeric',
            // 'category_id' => 'required|exists:categories,id',
            'images.*' => 'image|mimes:jpg, jpeg, png|max:2048',
        ]);

        DB::beginTransaction();

        try {
            $product->update([
                'name' => $validated['name'],
                'stock' => $validated['stock'],
                'price' => $validated['price'],
            ]);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $fileName = $file->getClientOriginalName();
                    $path = $file->storeAs('product', $fileName, 'public');
                    ProductImage::create([
                        'image' => $path,
                        'product_id' => $product->id,
                    ]);
                }
            }

            DB::commit();

            return redirect(route('product.index'))->with('success', 'Product has been updated succesfully');
        }
        catch (\Throwable $e) {
            DB::rollback();

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return redirect(route('product.index'))->with('success', 'Product has been deleted succesfully');
    }

    public function destroyImage($id)
    {
        $productImage = ProductImage::where('id', $id)->delete();

        if ($productImage) {
            return redirect()->back()->with('success', 'Image has been deleted succesfully');
        }
        else {
            return redirect()->back()->with('error', 'Failed to delete image');
        }
    }
}
