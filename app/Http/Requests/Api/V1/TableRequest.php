<?php

namespace App\Http\Requests\Api\V1;

use App\Models\RestaurantTable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(RestaurantTable::STATUSES)],
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['name'][0] = 'sometimes';
        }

        return $rules;
    }
}
