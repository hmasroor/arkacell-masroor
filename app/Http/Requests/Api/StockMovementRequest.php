<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function tenantId(): int
    {
        return request()->attributes->get('tenant')->id;
    }

    public function rules(): array
    {
        return [
            'type' => [
                'required',
                Rule::in(['in', 'out', 'transfer']),
            ],

            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')
                    ->where('tenant_id', $this->tenantId()),
            ],

            'warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id')
                    ->where('tenant_id', $this->tenantId()),
            ],

            'destination_warehouse_id' => [
                'nullable',
                'integer',
                'required_if:type,transfer',
                'different:warehouse_id',
                Rule::exists('warehouses', 'id')
                    ->where('tenant_id', $this->tenantId()),
            ],

            'quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            'reference' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}
