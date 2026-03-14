<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('editar_productos');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100', 'unique:products,sku,'.$this->route('record')],
            'barcode' => ['nullable', 'string', 'max:100', 'unique:products,barcode,'.$this->route('record')],
            'category_id' => ['required', 'exists:categories,id'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0', 'gte:cost_price'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'max_stock' => ['nullable', 'integer', 'min:0', 'gte:min_stock'],
            'active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'sale_price.gte' => 'El precio de venta no puede ser menor al costo.',
            'max_stock.gte' => 'El stock máximo no puede ser menor al stock mínimo.',
            'sku.unique' => 'Este SKU ya está en uso por otro producto.',
            'barcode.unique' => 'Este código de barras ya está en uso.',
            'cost_price.min' => 'El costo no puede ser negativo.',
            'sale_price.min' => 'El precio de venta no puede ser negativo.',
        ];
    }
}
