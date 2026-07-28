<!DOCTYPE html>
<html>
<head>
    <style>
        /* CSS styles for your invoice go here */
        body {
            font-family:'Times New Roman', Times, serif;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
        }
        .invoice-details {
            margin-top: 20px;
        }
        .order-items {
            margin-top: 20px;
        }
        .order-items table {
            width: 100%;
            border-collapse: collapse;
        }
        .order-items th, .order-items td {
            border-top: 1px solid #ddd;
            text-align: left;
            padding: 8px;
        }
        .order-items th {
            background-color: #f2f2f2;
        }
        .footer {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="company-address">
                <h2><strong>Invoice</strong></h2>
                <strong>{{ config('app.name') }} Sdn Bhd</strong><br />
                - HQ Address -<br />
                Kuala Lumpur 52200<br />
                Wilayah Persekutuan Kuala Lumpur<br />
                Malaysia<br />
                Tel: 012-211 9687
            </div>
        </div>
        <div class="invoice-details">
            @if($void)
            <h3 class="text-danger">VOID / CANCELLED ON {{ Carbon\Carbon::now()->format('d/m/Y') }}</h3>
            @endif
            Invoice No.: {{ $invoice_number }}<br />
            Order Date: {{ Carbon\Carbon::parse($order->created_at)->format('d/m/Y') }}<br />
            For: {{ $order->customer->name }}<br />
            Attn:<br />
            {{ $order->attn_name }}<br/>
            {{ $order->attn_contact }}<br/>
            Billing Address:<br />
            {{ $order->billing_address }}<br />
            {{ $order->billing_postcode.', '.$order->billing_state }}<br />
        </div>
        <div class="order-items">
            <h2>Order Items</h2>
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Product Name</th>
                        <th>Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order_items as $item_no => $item)
                        <tr>
                            <td>{{ $item_no + 1 }}</td>
                            <td>
                                {{ $item->name }}<br />
                                @foreach($item->options as $opt => $opt_itm)
                                {{ $opt }}: {{ $opt_itm }}<br />
                                @endforeach
                                @if($item->remark)
                                Customer Remark: {{ $item->remark }}<br />
                                @endif
                            </td>
                            <td>{{ $item->quantity }}</td>
                        </tr>
                    @endforeach
                    <tr style="border-top: #000 double 1px">
                        <td></td>
                        <td>Total</td>
                        <td>{{ $total_quantity }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="footer">
            <p>Payment Method: {{ __('user.payment_method.'.$order->payment_method) }}</p>
            This is computer generated invoice no signature required.
        </div>
    </div>
</body>
</html>
