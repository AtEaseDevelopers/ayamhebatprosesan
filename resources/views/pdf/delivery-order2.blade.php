<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delivery Order</title>
    <style>
        @page { margin: 0.25in 0.35in; }
        body { margin: 0; padding: 0; font-family: sans-serif; }
        table { border-collapse: collapse; }
        .header-logo { height: 70px; width: 70px; }
        .cert-logo { height: 45px; width: 45px; }
        .qr-code { height: 55px; width: 55px; }
        .company-name { font-size: 16px; font-weight: 700; }
        .company-info { font-size: 10px; line-height: 1.25; }
        .section-label { font-size: 10px; font-weight: 700; }
        .section-text { font-size: 10px; line-height: 1.2; }
        .items-table { width: 100%; margin-top: 4px; }
        .items-table thead td {
            font-size: 10px;
            font-weight: 700;
            border-top: 1px solid black;
            border-bottom: 1px solid black;
            padding: 3px 2px;
            line-height: 1.2;
            vertical-align: top;
        }
        .items-table tbody td {
            font-size: 10px;
            padding: 5px 2px;
            line-height: 1.4;
            vertical-align: top;
        }
        .footer-bank { font-size: 8px; font-weight: 700; line-height: 1.2; }
        .footer-remark { font-size: 9px; }
        .signature-text { font-size: 10px; line-height: 1.2; }
        .signature-line {
            font-size: 10px;
            text-align: center;
            border-top: 1px solid black;
            padding-top: 3px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <table style="width: 100%;">
        <tr>
            <td style="width: 18%; text-align: center; vertical-align: middle;">
                <img src="{{ public_path('assets/images/logo.jpg') }}" alt="" class="header-logo">
            </td>
            <td style="text-align: center; width: 64%;">
                <span class="company-name">AYAM HEBAT PROSESAN SDN. BHD.</span><br>
                <span class="company-info">(1130071.K)</span><br>
                <span class="company-info">Lot 698, Mk 12, Jalan Bukit Tambun,</span><br>
                <span class="company-info">Juru Estate, Juru, 14000 Bukit Mertajam, Pulau Pinang.</span><br>
                <span class="company-info">Phone : 012-496 2977 / 017-411 2599 / 012-425 8872.</span><br>
                <span class="company-info">A/C Dept : 012-522 2872</span>
            </td>
            <td style="width: 9%; text-align: center; vertical-align: top;">
                <img src="{{ public_path('assets/images/mesti-logo2.jpg') }}" alt="" class="cert-logo">
            </td>
            <td style="width: 9%; text-align: center; vertical-align: top;">
                <img src="{{ public_path('assets/images/mesti-logo.jpg') }}" alt="" class="cert-logo">
            </td>
        </tr>
    </table>

    <!-- Addresses & DO info -->
    <table style="width: 100%; margin-top: 6px;">
        <tr>
            <td style="width: 70%; vertical-align: top;">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 50%; vertical-align: top;">
                            <span class="section-label">BILLING ADDRESS :</span><br>
                            <span class="section-label">{{ $order->customer->name }}</span><br>
                            <span class="section-text">{{ $order->billing_address }}</span>
                        </td>
                        <td style="width: 50%; vertical-align: top;">
                            <span class="section-label">DELIVERY ADDRESS :</span><br>
                            <span class="section-text">{{ $order->shipping_address }}</span>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 30%; vertical-align: top;">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 30%;"><span class="section-label">DO NO.</span></td>
                        <td style="width: 5%;">:</td>
                        <td><span class="section-label">{{ $do_no }}</span></td>
                    </tr>
                    <tr>
                        <td><span class="section-text">DATE</span></td>
                        <td>:</td>
                        <td><span class="section-text">{{ $date }}</span></td>
                    </tr>
                    <tr>
                        <td><span class="section-text">PO No.</span></td>
                        <td>:</td>
                        <td><span class="section-text">{{ $order->po_no ?? '' }}</span></td>
                    </tr>
                    <tr>
                        <td colspan="3" style="text-align: center; padding: 2px 0;">
                            <img src="{{ public_path('assets/images/qr.jpeg') }}" alt="QR Code" class="qr-code">
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- A/C info -->
    <table style="width: 100%; margin-top: 4px;">
        <tr>
            <td style="width: 30%;">
                <span class="section-label">A/C NO : <span style="font-weight: 100;">{{ $order->customer->sql_customer_code ?? '-' }}</span></span>
            </td>
            <td style="width: 70%;">
                <span class="section-label">TEL : <span style="font-weight: 100;">{{ $order->customer->attn_contact }}</span></span>
            </td>
        </tr>
        <tr>
            <td></td>
            <td>
                <span class="section-label">FAX : <span style="font-weight: 100;">{{ $order->customer->fax_no }}</span></span>
            </td>
        </tr>
    </table>

    <!-- Items -->
    @php
        $total_weight = 0;
    @endphp
    <table class="items-table">
        <thead>
            <tr>
                <td style="width: 6%;">NO.</td>
                <td style="width: 34%;">DESCRIPTION</td>
                <td style="width: 10%;">QTY</td>
                <td style="width: 30%;">REMARK</td>
                <td style="width: 20%; text-align: center;">WEIGHT</td>
            </tr>
        </thead>
        <tbody>
            @foreach ($order_items as $key => $prod)
                <tr>
                    <td>{{ $key + 1 }}</td>
                    <td>{{ $prod->name }}</td>
                    <td>{{ $prod->show_qty == true ? ($prod->quantity ?? 0) : '' }}</td>
                    <td>{{ $prod->remark }}</td>
                    <td style="text-align: center;">{{ $prod->show_weight == true ? (($prod->quantity != null && $prod->product_weight != null ? $prod->product_weight * $prod->quantity : $prod->weight) . ' KG') : '' }}</td>
                </tr>
                @php
                    if ($prod->show_weight == true) {
                        $total_weight += $prod->weight;
                    }
                @endphp
            @endforeach
        </tbody>
    </table>

    <!-- Footer -->
    <table style="width: 100%; margin-top: 18px;">
        <tr>
            <td colspan="3">
                <span class="footer-bank">
                    AYAM HEBAT PROSESAN SDN. BHD.<br>
                    BANK ACC - PUBLIC BANK BERHAD 381 089 9010
                </span>
            </td>
        </tr>
        <tr>
            <td style="border-top: 1px solid black; border-bottom: 1px solid black;" colspan="2"></td>
            <td style="font-size: 10px; border-top: 1px solid black; border-bottom: 1px solid black; font-weight: 700; text-align: right; padding: 2px 0;">Total Weight : {{ $total_weight }} KG</td>
        </tr>
        <tr>
            <td colspan="3" style="padding-top: 2px;">
                <span class="footer-remark">Remark : Please return a carbon copy to Ayam Hebat Prosesan Sdn. Bhd.</span>
            </td>
        </tr>
    </table>

    <!-- Signature -->
    <table style="width: 100%; margin-top: 8px;">
        <tr>
            <td style="padding-bottom: 35px;"></td>
            <td style="width: 8%;"></td>
            <td style="padding-bottom: 65px;">
                <span class="signature-text">I/We hereby confirmed and received to the above mentioned goods in a good order & condition.</span>
            </td>
        </tr>
        <tr>
            <td class="signature-line" style="padding-top: 20px;">Authorised Signature</td>
            <td></td>
            <td class="signature-line" style="padding-top: 20px;">Customer Company Stamp & Signature</td>
        </tr>
    </table>
</body>
</html>
