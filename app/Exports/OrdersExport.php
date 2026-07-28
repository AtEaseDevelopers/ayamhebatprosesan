<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use DB;

class OrdersExport implements FromCollection, WithHeadings, WithEvents, WithColumnWidths
{
    protected $i;
    protected $orders;

    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function (AfterSheet $event) {
                $sheet = $event->sheet;

                $total_sales_count = 0;
                $total_quantity_sold = 0;
                $total_sales = 0;
                $col_no = 1;
                $pre_order_id = null;

                foreach ($this->orders as $key => $order) {
                    $no = $this->i;

                    if ($pre_order_id == $order->id) {
                        $sheet->setCellValue('A' . $no, '');
                        $sheet->setCellValue('B' . $no, '');
                        $sheet->setCellValue('C' . $no, '');
                        $sheet->setCellValue('D' . $no, '');
                    } else {
                        $total_sales_count++;
                        $sheet->setCellValue('A' . $no, $col_no++);
                        $sheet->setCellValue('B' . $no, $order->created_at);
                        $sheet->setCellValue('C' . $no, $order->lorry_number);
                        $sheet->setCellValue('D' . $no, $order->name);
                    }

                    $sheet->setCellValue('E' . $no, $order->product_name);
                    $sheet->setCellValue('F' . $no, $order->sku);
                    $sheet->setCellValue('G' . $no, $order->quantity);

                    if ($pre_order_id == $order->id) {
                        $sheet->setCellValue('H' . $no, '');
                    } else {
                        $sheet->setCellValue('H' . $no, $order->updated_at);
                    }

                    $this->i++;

                    $total_quantity_sold += $order->quantity;
                    $total_sales += $order->price;
                    $pre_order_id = $order->id;
                }

                $no = $no + 3;

                $sheet->setCellValue('A' . $no, 'TOTAL SALES COUNT:');
                $sheet->setCellValue('B' . $no, $total_sales_count);

                $sheet->setCellValue('C' . $no, 'TOTAL QUANTITY SOLD:');
                $sheet->setCellValue('D' . $no, $total_quantity_sold);

                $sheet->setCellValue('E' . $no, 'TOTAL SALES:');
                $sheet->setCellValue('F' . $no, $total_sales);

                // Make row bold
                $event->sheet->getDelegate()->getStyle('A1:H1')->getFont()->setBold(true);

                // Set BG color
                $event->sheet->getDelegate()->getStyle('A1:H1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dee0bb');

                // Set Font color
                $event->sheet->getDelegate()->getStyle('A1:H1')->getFont()->getColor()->setARGB('000000');
            }
        ];
    }

    public function collection()
    {
        $this->i = 2;

        $request = request();
        $orderId = $request->id;
        $fdate = $request->fdate;
        $tdate = $request->tdate;
        $status = $request->status;
        $driver = $request->driver;
        $customer = $request->customer;
        $area = $request->area;
        $ids = explode(',', $request->orders_id) ?? [];

        $this->orders = DB::table('order_products')
            ->join('orders', 'orders.id', '=', 'order_products.order_id')
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->join('drivers', 'drivers.id', '=', 'orders.driver_id')
            ->join('products', 'products.id', '=', 'order_products.product_id')
            ->select(
                'orders.id',
                'orders.created_at',
                'users.name',
                'order_products.product_name',
                'products.sku',
                'order_products.quantity',
                'order_products.price',
                'drivers.lorry_number',
                'orders.updated_at'
            )
            ->where('order_products.status','!=','removed')
            ->when(
                $request->id, function ($q) {
                    return $q->where('orders.id', request()->id);
                }
            )
            ->when(
                $request->fdate, function ($q) {
                    return $q->whereDate('orders.created_at', '>=', request()->fdate);
                }
            )
            ->when(
                $request->tdate, function ($q) {
                    return $q->whereDate('orders.created_at', '<=', request()->tdate);
                }
            )
            ->when(
                $request->status, function ($q) {
                    return $q->where('orders.status', request()->status);
                }
            )
            ->when(
                $request->driver, function ($q) {
                    return $q->where('orders.driver_id', request()->driver);
                }
            )
            ->when(
                $request->customer, function ($q) {
                    return $q->where('orders.user_id', request()->customer);
                }
            )
            ->when(
                $request->area, function ($q) {
                    return $q->where('orders.area', request()->area);
                }
            )
            ->when(
                count($ids), function ($q) use ($ids) {
                    return $q->whereIn('orders.id', $ids);
                }
            )
            ->get();

        return collect([]);
    }

    public function headings(): array
    {
        return [
            [
                'No',
                'Order At',
                'Lorry Number',
                'Customer',
                'Item Name',
                'Item SKU',
                'Item Quantity',
                'Last Updated At',
            ]
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 20,
            'C' => 20,
            'D' => 15,
            'E' => 25,
            'F' => 15,
            'G' => 15,
            'H' => 20,
        ];
    }
}
