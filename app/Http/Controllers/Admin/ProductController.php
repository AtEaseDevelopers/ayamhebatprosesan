<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AdminProductExport;
use App\Helper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Product;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth_admin');
    }

    public function index(Request $request)
    {
        $products = Product::select("*");
        $products->where('status', '!=', Product::$status['removed']);

        if ($filter_sku = $request->input('sku')) {
            $products->where('sku', 'LIKE', "%$filter_sku%");
        }

        if ($filter_name = $request->input('name')) {
            $products->where('name', 'LIKE', "%$filter_name%");
        }

        if ($filter_status = $request->input('status')) {
            $products->where('status', $filter_status);
        }

        if ($filter_fdate = $request->input('fdate')) {
            $products->where('created_at', '>=', $filter_fdate);
        }

        if ($filter_tdate = $request->input('tdate')) {
            $products->where('created_at', '<=', $filter_tdate." 23:59:59");
        }

        if ($filter_price_range = $request->input('price_range')) {
            $filter_price_range = explode(',', $filter_price_range);
            $from_price = $filter_price_range[0];
            $to_price = $filter_price_range[1];
            $products->where('price', '>=', $from_price);
            $products->where('price', '<=', $to_price);
        }

        $products = $products->paginate(15);
        $minPrice = Product::min('price');
        $maxPrice = Product::max('price');
        foreach ($products as $key => $value) {
            $image = json_decode($value->images, true);
            if (isset($image[0])) {
                $products[$key]->image_url = url('/') . '/' . Product::$path."/".$value->id."/".$image[0];
            } else {
                $products[$key]->image_url = asset('assets/images/product-default.jpg');
            }
        }
        
        return view(
            'admin.products.index', [
                'products' => $products,
                'input' => $request->all() + ['min_price' => $minPrice, 'max_price' => $maxPrice, 'from_price' => $from_price ?? $minPrice, 'to_price' => $to_price ?? $maxPrice],
                'query_params' => Helper::query_params($request->input()),
            ]
        );
    }

    public function export(Request $request)
    {
        $products = Product::select('id', 'name', 'description', 'price', 'status', 'created_at');
        
        if ($filter_sku = $request->input('sku')) {
            $products->where('sku', 'LIKE', "%$filter_sku%");
        }

        if ($filter_name = $request->input('name')) {
            $products->where('name', 'LIKE', "%$filter_name%");
        }

        if ($filter_status = $request->input('status')) {
            $products->where('status', $filter_status);
        }

        if ($filter_price_range = $request->input('price_range')) {
            $filter_price_range = explode(',', $filter_price_range);
            $from_price = $filter_price_range[0];
            $to_price = $filter_price_range[1];
            $products->where('price', '>=', $from_price);
            $products->where('price', '<=', $to_price);
        }

        $header = ['No', 'Name', 'Description', 'Price', 'Status', 'Created At']; // Adjust the header based on your data model
        return Excel::download(new AdminProductExport($products->get(), $header), Carbon::now()->format('YmdHis').'-Product-List.xlsx');
    }
}
