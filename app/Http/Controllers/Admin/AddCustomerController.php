<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\CustomerOption;
use App\CustomerOptionItem;
use App\Helper;
use App\ProductVisibility;
use App\System;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AddCustomerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth_admin');
    }

    public function showForm()
    {
        $category_list = User::select('category')
            ->groupBy('category')   
            ->pluck('category')
            ->toArray();

        $products = DB::table('products')->select('id', 'name', 'sku')->get()->toArray();
        $drivers = DB::table('drivers')->select('id', 'lorry_number')->get()->toArray();

        return view(
            'admin.customers.create', [
                'products' => $products,
                'drivers' => $drivers,
                'category_list' => $category_list,
                'payment_method_options' => User::$payment_method,
                'shipping_state_options' => System::$country_state['MY'],
                'areaList' => Helper::areaList()
            ]
        );
    }

    public function addCustomer(Request $request)
    {
        $data = $this->validateAddCustomer($request);

        if (isset($data['error']) && $data['error']) {
            return back()->withInput()->withErrors($data['field_err']);
        }

        // generate login code for specific user, unique for every user
        do {
            $login_code = Helper::generateRandomString(100);
            $exist = User::where('login_code', $login_code)->exists();
        } while ($exist);
        
        $default_password = "ecommerce123";
        $customer = User::create(
            [
                "name" => $data['name'],
                "email" => $data['email'] ?? null,
                "category" => $data['category'],
                "attn_name" => $data['attn_name'],
                "attn_contact" => $data['attn_contact'],
                "payment_method" => isset($data['payment_method']) ? json_encode($data['payment_method']) : null,
                "login_code" => $login_code,
                "area" => $request['area'],
                "billing_address" => $data['billing_address'],
                "billing_city" => $request['billing_city'] ?? null,
                "shipping_city" => $request['shipping_city'] ?? null,
                "billing_postcode" => $data['billing_postcode'] ?? null,
                "billing_state" => $data['billing_state'] ?? null,
                "shipping_address" => $data['shipping_address'] ?? '',
                "shipping_postcode" => $data['shipping_postcode'] ?? '',
                "shipping_state" => $data['shipping_state'] ?? '',
                "fax_no" => $data['fax_no'] ?? '',
                "password" => Hash::make($default_password),
                "status" => $request['status'] ?? User::$user_status['active'],
                "remark" => $data['remark'],
                "price_permission" => $request['price_permission'] ?? 0,
                "invoice_visibility" => $request['invoice_visibility'] ?? 0,
                "invoice_price_permission" => $request['invoice_price_permission'] ?? 0,
                "default_driver_id" => $request['default_driver_id'],
                'sql_customer_code' => $request['sql_customer_code'] ?? null,
            ]
        );

        if ($request['product_id']) {
            foreach ($request['product_id'] as $pid) {
                ProductVisibility::create([
                    'user_id' => $customer->id, 
                    'product_id' => $pid,
                ]);
            }
        }

        return redirect(route('admin.customers'))->with('success', "$customer->name has been added successfully. Default login password is $default_password.");

    }

    public function validateAddCustomer(Request $request)
    {
        $rules = [
            "name" => array_merge(User::$attribute_rules['name'], []),
            "email" => array_merge(User::$attribute_rules['email'], ['unique:users,email']),
            "category" => array_merge(User::$attribute_rules['category'], []),
            "attn_name" => array_merge(User::$attribute_rules['attn_name'], []),
            "attn_contact" => array_merge(User::$attribute_rules['attn_contact'], []),
            // "payment_method" => array_merge(User::$attribute_rules['payment_method'], []),
            "payment_method" => ['nullable'],
            "billing_address" => array_merge(User::$attribute_rules['billing_address'], []),
            // "billing_postcode" => array_merge(User::$attribute_rules['billing_postcode'], []),
            'billing_postcode' => ['nullable'],
            // "billing_state" => array_merge(User::$attribute_rules['billing_state'], []),
            'billing_state' => ['nullable'],
            "shipping_address" => array_merge(User::$attribute_rules['shipping_address'], []),
            // "shipping_postcode" => array_merge(User::$attribute_rules['shipping_postcode'], []),
            'shipping_postcode' => ['nullable'],
            // "shipping_state" => array_merge(User::$attribute_rules['shipping_state'], []),
            'shipping_state' => ['nullable'],
            "remark" => array_merge(User::$attribute_rules['remark'], []),
            "fax_no" => array_merge(User::$attribute_rules['fax_no'], []),

        ];

        try {
            $data = $request->validate($rules);
        } catch (ValidationException $err) {
            return [
                'error' => $err->getMessage(),
                'field_err' => $err->validator->errors()->getMessages(),
            ];
        }

        return $data;
    }
}
