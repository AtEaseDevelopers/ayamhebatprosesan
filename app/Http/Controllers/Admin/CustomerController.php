<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Exports\AdminCustomerExport;
use Illuminate\Support\Facades\Crypt;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Helper;
use App\Imports\CustomersImport;
use App\ProductVisibility;
use App\System;
use App\User;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth_admin');
    }

    public function index(Request $request)
    {
        $users = User::select("users.*", "drivers.lorry_number")
            ->leftJoin('drivers', 'users.default_driver_id', '=', 'drivers.id');

        if ($filter_name = $request->input('name')) {
            $users->where('users.name', 'LIKE', "%$filter_name%");
        }

        if ($filter_email = $request->input('email')) {
            $users->where('users.email', 'LIKE', "%$filter_email%");
        }

        if ($filter_category = $request->input('category')) {
            $users->where('users.category', $filter_category);
        }

        if ($filter_area = $request->input('area')) {
            $users->where('users.area', $filter_area);
        }

        if ($filter_shipping_state = $request->input('shipping_state')) {
            $users->where('users.shipping_state', $filter_shipping_state);
        }

        if ($filter_status = $request->input('status')) {
            $users->where('users.status', $filter_status);
        }

        $users = $users->paginate(15);
        foreach ($users as $key => $value) {
            $payment_method = json_decode($value->payment_method, true);
            $payment_method_text = [];
            if ($payment_method != null) {
                foreach ($payment_method as $pm) {
                    $payment_method_text[] = trans('user.payment_method.'.$pm);
                }
            }
            $users[$key]->payment_method_text = $payment_method_text;
            $users[$key]->formatted_billing_address = $value->billing_address.", ".$value->city.", ".$value->billing_postcode.", ".$value->billing_state;
            $users[$key]->formatted_shipping_address = $value->shipping_address.", ".$value->shipping_postcode.", ".$value->shipping_state;
            $users[$key]->fast_login_link = url('fast-login/'.Crypt::encryptString($value->login_code));
        }
        
        $category_list = User::select('category')
            ->groupBy('category')   
            ->pluck('category')
            ->toArray();

        return view('admin.customers.index', [
                'category_list' => $category_list,
                'users' => $users,
                'input' => $request->all(),
                'query_params' => Helper::query_params($request->input()),
                'shipping_state_options' => System::$country_state['MY'],
                'areaList' => Helper::areaList()
            ]
        );
    }

    public function export(Request $request)
    {
        $users = User::select('users.name', 'users.email', 'users.category', 'drivers.lorry_number', 'users.shipping_address', 'users.shipping_postcode', 'users.shipping_state', 'users.remark', 'users.status', 'users.created_at as join_date')
            ->leftJoin('drivers', 'users.default_driver_id', '=', 'drivers.id');

        if ($filter_name = $request->input('name')) {
            $users->where('users.name', 'LIKE', "%$filter_name%");
        }

        if ($filter_email = $request->input('email')) {
            $users->where('users.email', 'LIKE', "%$filter_email%");
        }

        if ($filter_category = $request->input('category')) {
            $users->where('users.category', $filter_category);
        }

        if ($filter_shipping_state = $request->input('shipping_state')) {
            $users->where('users.shipping_state', $filter_shipping_state);
        }

        if ($filter_status = $request->input('status')) {
            $users->where('users.status', $filter_status);
        }

        $header = ['Name', 'Email', 'Category', 'Lorry Number', 'Shipping Address', 'Shipping Postcode', 'Shipping State', 'Remark', 'Status', 'Created At'];
        return Excel::download(new AdminCustomerExport($users->get(), $header), Carbon::now()->format('YmdHis').'-Customer-List.xlsx');
    }

    public function deleteCustomerProduct(Request $request)
    {
        ProductVisibility::where('id', $request['id'])->delete();
        return response()->json([]);
    }

    public function import_customers()
    {
        return view('admin.customers.import_customers');
    }

    public function import_customers_submit(Request $request)
    {
        $request->validate(
            [
                'file' => 'required|file|mimes:xlsx,csv',
            ]
        );

        Excel::import(new CustomersImport, $request->file('file'));
        try {
            // Import the file with transaction handling inside the import
            return back();
        } catch (\Exception $e) {
            // Catch the exception thrown during the import process and display it
            return back();
        }
    }
}
