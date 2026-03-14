<?php

namespace Database\Seeders;

use App\Models\ReceiptTemplate;
use Illuminate\Database\Seeder;

class ReceiptTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Ticket de Venta Estandar',
                'type' => 'sale_ticket',
                'is_active' => true,
                'content_html' => file_get_contents(resource_path('views/pdf/ticket.blade.php')),
            ],
            [
                'name' => 'Factura de Venta A4',
                'type' => 'sale_invoice',
                'is_active' => true,
                'content_html' => file_get_contents(resource_path('views/pdf/invoice.blade.php')),
            ],
            [
                'name' => 'Comprobante de Compra (Ticket)',
                'type' => 'purchase_ticket',
                'is_active' => true,
                'content_html' => file_get_contents(resource_path('views/pdf/purchase_ticket.blade.php')),
            ],
            [
                'name' => 'Comprobante de Compra (Factura A4)',
                'type' => 'purchase_invoice',
                'is_active' => true,
                'content_html' => file_get_contents(resource_path('views/pdf/purchase_invoice.blade.php')),
            ],
            [
                'name' => 'Reporte de Caja',
                'type' => 'cash_register_report',
                'is_active' => true,
                'content_html' => file_get_contents(resource_path('views/pdf/cash_register_report.blade.php')),
            ],
        ];

        foreach ($templates as $template) {
            ReceiptTemplate::firstOrCreate(
                ['type' => $template['type']],
                $template
            );
        }
    }
}
