<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('crear_ajustes_inventario');
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'reason' => ['required', 'string', 'in:conteo_fisico,daño,robo,devolucion,correccion,otro'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.adjustment_quantity' => ['required', 'integer', 'not_in:0'],
            'items.*.reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Debe seleccionar un motivo para el ajuste.',
            'reason.in' => 'El motivo seleccionado no es válido.',
            'items.required' => 'Debe agregar al menos un producto al ajuste.',
            'items.*.adjustment_quantity.not_in' => 'La cantidad de ajuste no puede ser cero.',
        ];
    }
}
