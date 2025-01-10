<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|max:255',
            'document' => 'required',
            'email' => [
                'required',
                'email',
                Rule::unique('suppliers')->ignore($this->supplier)
            ],
            'phones' => 'required|array',
            'phones.*.number' => 'required|string',
            'phones.*.is_main' => 'required|boolean',
            'document_type' => 'required|max:1',
            'address.cep' => 'required|string',
            'address.city' => 'required|string',
            'address.complement' => 'nullable|string',
            'address.neighborhood' => 'required|string',
            'address.number' => 'required',
            'address.state' => 'required|string|size:2',
            'address.street' => 'required|string',
        ];
    }
}
