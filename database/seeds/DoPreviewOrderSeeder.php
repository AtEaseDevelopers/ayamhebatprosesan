<?php

use Illuminate\Database\Seeder;
use App\Order;
use App\OrderProduct;
use App\Product;
use App\User;
use App\PdfHelper;

class DoPreviewOrderSeeder extends Seeder
{
    public function run()
    {
        $user = User::where('status', 'active')->first();

        if (!$user) {
            $this->command->error('No active customer found.');
            return;
        }

        $products = Product::where('status', Product::$status['active'])
            ->take(15)
            ->get();

        if ($products->count() < 15) {
            $products = Product::take(15)->get();
        }

        if ($products->isEmpty()) {
            $this->command->error('No products found.');
            return;
        }

        $total = 0;
        $orderWeight = 0;

        $order = Order::create([
            'user_id' => $user->id,
            'total_price' => 0,
            'attn_name' => $user->attn_name ?? $user->name,
            'attn_contact' => $user->attn_contact,
            'payment_method' => null,
            'area' => $user->area ?? null,
            'billing_address' => $user->billing_address ?? '123 Test Street, 14000 Bukit Mertajam, Pulau Pinang',
            'billing_city' => $user->billing_city ?? null,
            'billing_postcode' => $user->billing_postcode ?? '14000',
            'billing_state' => $user->billing_state ?? 'Pulau Pinang',
            'shipping_address' => $user->shipping_address ?? $user->billing_address ?? '123 Test Street, 14000 Bukit Mertajam, Pulau Pinang',
            'shipping_postcode' => $user->shipping_postcode ?? '14000',
            'shipping_state' => $user->shipping_state ?? 'Pulau Pinang',
            'status' => Order::$status['completed'],
            'po_no' => 'TEST-15-ITEMS',
            'do_date' => now()->format('Y-m-d'),
        ]);

        foreach ($products as $index => $product) {
            $quantity = $index + 1;
            $unitPrice = Product::get_today_price($product->id, $user) ?: $product->price;
            $price = $unitPrice * $quantity;
            $weight = ($product->weight === '' || $product->weight === null) ? 0 : $product->weight * $quantity;

            OrderProduct::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'weight' => $weight,
                'product_weight' => $product->weight ?: null,
                'unit_price' => $unitPrice,
                'price' => $price,
                'remark' => 'Test item ' . ($index + 1),
                'nos' => null,
                'status' => OrderProduct::$status['active'],
            ]);

            $total += $price;
            $orderWeight += $weight;
        }

        $order->update([
            'total_price' => $total,
            'order_weight' => $orderWeight,
        ]);

        PdfHelper::GenerateDeliveryOrder($order);

        $pdfUrl = url('/') . '/' . Order::$path . '/' . $order->id . '/delivery-order-' . $order->id . '.pdf';
        $adminUrl = url('/admin/orders/' . $order->id);

        if ($this->command) {
            $this->command->info('Created order #' . $order->id . ' with ' . $products->count() . ' items.');
            $this->command->info('DO No: ' . $order->fresh()->do_no);
            $this->command->info('View DO: ' . $pdfUrl);
            $this->command->info('Admin order: ' . $adminUrl);
        }
    }
}
