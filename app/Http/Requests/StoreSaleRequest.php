<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('crear_ventas');
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'exists:customers,id'],
            'cash_register_id' => ['required', 'exists:cash_registers,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'document_type' => ['required', 'in:ticket,factura'],
            'payment_method' => ['required', 'in:efectivo,tarjeta,transferencia,qr,credito'],
            'amount_received' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Debe agregar al menos un producto a la venta.',
            'items.min' => 'Debe agregar al menos un producto a la venta.',
            'items.*.quantity.min' => 'La cantidad mínima es 1.',
            'items.*.unit_price.min' => 'El precio no puede ser negativo.',
            'cash_register_id.required' => 'Debe seleccionar una caja registradora.',
            'amount_received.min' => 'El monto recibido no puede ser negativo.',
        ];
    }
}
