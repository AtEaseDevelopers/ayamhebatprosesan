<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PDF;
use Auth;
use Carbon\Carbon;

class PdfHelper extends Model
{
    public static function GenerateOrderInvoice(Order $order, $void = false)
    {
        $order_products = DB::table('order_products')
            ->select(
                'order_products.id as order_product_id', 
                'products.id as product_id', 
                'products.show_qty as show_qty',
                'products.show_weight as show_weight',
                'orders.id as order_id', 
                'orders.transfer_slip as transfer_slip', 
                'order_products.product_name as name', 
                'order_products.quantity', 
                'order_products.unit_price', 
                'order_products.price',
                'order_products.remark',
                'order_products.nos',
                'order_products.weight',
                'order_products.product_weight'
            )
            ->leftJoin('orders', 'orders.id', '=', 'order_products.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_products.product_id')
            ->where('order_products.status', OrderProduct::$status['active'])
            ->where('orders.id', $order->id)
            ->get();

        $total = 0;
        $total_quantity = 0;
        foreach ($order_products as $key => $value) {
            $order_products[$key]->options = OrderProduct::getOption($value->order_product_id);

            $transfer_slip_url = "";
            if ($order->payment_method == User::$payment_method['bank-transfer']) {
                $transfer_slip_url = url('/').Order::$path.'/'.$value->order_id.'/'.$value->transfer_slip;
            }
            $order_products[$key]->transfer_slip_url = $transfer_slip_url;
            
            $total += $order_products[$key]->unit_price * $value->quantity;
            $total_quantity += $value->quantity;
        }

        $data = [
            'invoice_number' => 'INV-'.$order->id,
            'date' => now()->format('d/m/Y'),
            'order' => $order,
            'order_items' => $order_products,
            'total_quantity' => $total_quantity,
            'total' => $total,
            'void' => $void,
            'user' => $order->customer,
            'do_no' => $order->do_no,
            // Add more invoice data as needed
        ];

        $pdf = PDF::loadView('pdf.invoice2', $data);
        $pdf->setPaper('a4', 'portrait'); // A4 size in portrait mode
    
        $invoiceFilename = 'invoice-' . $order->id . '.pdf';
    
        Storage::disk('local')->put(Order::$path.'/'.$order->id.'/'.$invoiceFilename, $pdf->output());
    }

    public static function GenerateOrderInvoiceWithoutPrice(Order $order, $void=false)
    {
        $order_products = DB::table('order_products')
            ->select(
                'order_products.id as order_product_id', 
                'products.id as product_id', 
                'products.show_qty as show_qty',
                'products.show_weight as show_weight',
                'orders.id as order_id', 
                'orders.transfer_slip as transfer_slip', 
                'order_products.product_name as name', 
                'order_products.quantity', 
                'order_products.unit_price', 
                'order_products.price',
                'order_products.remark',
                'order_products.nos',
                'order_products.weight',
                'order_products.product_weight'
            )
            ->leftJoin('orders', 'orders.id', '=', 'order_products.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_products.product_id')
            ->where('order_products.status', OrderProduct::$status['active'])
            ->where('orders.id', $order->id)
            ->get();

        $total = 0;
        $total_quantity = 0;
        foreach ($order_products as $key => $value) {
            $order_products[$key]->options = OrderProduct::getOption($value->order_product_id);

            $transfer_slip_url = "";
            if ($order->payment_method == User::$payment_method['bank-transfer']) {
                $transfer_slip_url = url('/').Order::$path.'/'.$value->order_id.'/'.$value->transfer_slip;
            }
            $order_products[$key]->transfer_slip_url = $transfer_slip_url;
            
            $total += $order_products[$key]->unit_price * $value->quantity;
            $total_quantity += $value->quantity;
        }

        $data = [
            'invoice_number' => 'INV-'.$order->id,
            'date' => now()->format('d/m/Y'),
            'order' => $order,
            'order_items' => $order_products,
            'total_quantity' => $total_quantity,
            'total' => $total,
            'void' => $void,
            'user' => $order->customer,
            'do_no' => $order->do_no,
        ];

        $pdf = PDF::loadView('pdf.invoice2withoutprice', $data);
        $pdf->setPaper('a4', 'portrait'); // A4 size in portrait mode
    
        $invoiceFilename = 'invoice2-' . $order->id . '.pdf';
    
        Storage::disk('local')->put(Order::$path.'/'.$order->id.'/'.$invoiceFilename, $pdf->output());
    }

    public static function GenerateDeliveryOrder(Order $order, $void=false)
    {
        $order_products = DB::table('order_products')
            ->select(
                'order_products.id as order_product_id', 
                'products.id as product_id', 
                'products.show_qty as show_qty',
                'products.show_weight as show_weight',
                'orders.id as order_id', 
                'orders.transfer_slip as transfer_slip', 
                'order_products.product_name as name', 
                'order_products.quantity', 
                'order_products.unit_price', 
                'order_products.price',
                'order_products.remark',
                'order_products.nos',
                'order_products.weight',
                'order_products.product_weight'
            )
            ->leftJoin('orders', 'orders.id', '=', 'order_products.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_products.product_id')
            ->where('order_products.status', OrderProduct::$status['active'])
            ->where('orders.id', $order->id)
            ->get();

        $total = 0;
        foreach ($order_products as $key => $value) {
            $order_products[$key]->options = OrderProduct::getOption($value->order_product_id);

            $transfer_slip_url = "";
            if ($order->payment_method == User::$payment_method['bank-transfer']) {
                $transfer_slip_url = url('/').Order::$path.'/'.$value->order_id.'/'.$value->transfer_slip;
            }
            $order_products[$key]->transfer_slip_url = $transfer_slip_url;
            
            $total += $order_products[$key]->unit_price * $value->quantity;
        }
        
        
        $prefix = 'AHP' . now()->format('ym'); // Prefix for the format AHP2501
        $latestOrder = Order::where('do_no', 'like', $prefix . '%')
            ->orderBy('do_no', 'desc')
            ->first();
        
        if ($latestOrder) {
            // Extract the running number part and increment it
            $lastNumber = (int) substr($latestOrder->do_no, strlen($prefix));
            $do_no_idx = $lastNumber + 1;
        } else {
            // Start from 1 if no previous record exists
            $do_no_idx = 1;
        }

        //$do_no_idx = Order::where('do_no', 'like', 'AHP'. now()->format('ym') . '%')->count();
        //$do_no_idx++;
        
        $ending_digit = sprintf('%04d', $do_no_idx);
        $do_no = 'AHP' . now()->format('ym') . $ending_digit;
        $order->do_no = $do_no;
        $order->save();

        $data = [
            'invoice_number' => 'INV-'.$order->id,
            'date' => $order->created_at,
            'order' => $order,
            'order_items' => $order_products,
            'total' => $total,
            'void' => $void,
            'do_no' => $do_no,
            // Add more invoice data as needed
        ];
    
        $pdf = PDF::loadView('pdf.delivery-order2', $data);
        $pdf->setPaper('a4', 'portrait'); // A4 size in portrait mode
    
        $invoiceFilename = 'delivery-order-' . $order->id . '.pdf';
    
        Storage::disk('local')->put(Order::$path.'/'.$order->id.'/'.$invoiceFilename, $pdf->output());
    }
    
    public static function UpdateDeliveryOrder(Order $order, $void=false, $custom_date=null)
    {
        $order_products = DB::table('order_products')
            ->select(
                'order_products.id as order_product_id', 
                'products.id as product_id', 
                'products.show_qty as show_qty',
                'products.show_weight as show_weight',
                'orders.id as order_id', 
                'orders.transfer_slip as transfer_slip', 
                'order_products.product_name as name', 
                'order_products.quantity', 
                'order_products.unit_price', 
                'order_products.price',
                'order_products.remark',
                'order_products.nos',
                'order_products.weight',
                'order_products.product_weight'
            )
            ->leftJoin('orders', 'orders.id', '=', 'order_products.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_products.product_id')
            ->where('order_products.status', OrderProduct::$status['active'])
            ->where('orders.id', $order->id)
            ->get();

        $total = 0;
        foreach ($order_products as $key => $value) {
            $order_products[$key]->options = OrderProduct::getOption($value->order_product_id);

            $transfer_slip_url = "";
            if ($order->payment_method == User::$payment_method['bank-transfer']) {
                $transfer_slip_url = url('/').Order::$path.'/'.$value->order_id.'/'.$value->transfer_slip;
            }
            $order_products[$key]->transfer_slip_url = $transfer_slip_url;
            
            $total += $order_products[$key]->unit_price * $value->quantity;
        }
        
        if ($order->do_no == null) {
            if ($custom_date != null) {
                $prefix = 'AHP' . Carbon::parse($custom_date)->format('ym'); // Prefix for the format AHP2501
            } else {
                $prefix = 'AHP' . now()->format('ym'); // Prefix for the format AHP2501
            }
            $latestOrder = Order::where('do_no', 'like', $prefix . '%')
                ->orderBy('do_no', 'desc')
                ->first();
            
            if ($latestOrder) {
                // Extract the running number part and increment it
                $lastNumber = (int) substr($latestOrder->do_no, strlen($prefix));
                $do_no_idx = $lastNumber + 1;
            } else {
                // Start from 1 if no previous record exists
                $do_no_idx = 1;
            }
    
            //$do_no_idx = Order::where('do_no', 'like', 'AHP'. now()->format('ym') . '%')->count();
            //$do_no_idx++;
            
            $ending_digit = sprintf('%04d', $do_no_idx);
            $do_no = $prefix . $ending_digit;
            $order->do_no = $do_no;
            $order->save();
        }
        
        $data = [
            'invoice_number' => 'INV-'.$order->id,
            'date' => $order->do_date,
            'order' => $order,
            'order_items' => $order_products,
            'total' => $total,
            'void' => $void,
            'do_no' => $order->do_no,
            // Add more invoice data as needed
        ];
    
        $pdf = PDF::loadView('pdf.delivery-order2', $data);
        $pdf->setPaper('a4', 'portrait'); // A4 size in portrait mode
    
        $invoiceFilename = 'delivery-order-' . $order->id . '.pdf';
    
        Storage::disk('local')->put(Order::$path.'/'.$order->id.'/'.$invoiceFilename, $pdf->output());
    }
}
