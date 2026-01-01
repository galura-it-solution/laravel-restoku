<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class MenuRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $rawValue = $this->input('is_active');

            if (is_string($rawValue)) {
                $value = trim($rawValue);

                if ($value === '' || strtolower($value) === 'null') {
                    $this->merge(['is_active' => null]);
                    return;
                }

                $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                if ($normalized !== null) {
                    $this->merge(['is_active' => $normalized]);
                }
            }
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'image_object_key' => ['nullable', 'string', 'max:2048'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
            'stock' => ['nullable', 'integer', 'min:0'],
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            foreach (['category_id', 'name', 'price'] as $field) {
                $rules[$field][0] = 'sometimes';
            }
        }

        return $rules;
    }
}
