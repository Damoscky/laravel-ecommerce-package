<?php

namespace SbscPackage\Ecommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => 'required|integer|gt:0',
            'sub_category_id' => 'required|integer|gt:0',
            'product_name' => 'required|string|min:3|max:250',
            'long_description' => 'sometimes|min:5',
            'short_description' => 'sometimes|min:5',
            'quantity_supplied' => 'required|integer|gt:0',
            'minimum_purchase_per_quantity' => 'sometimes',
            "brand_name" => "sometimes|nullable|max:250",
            "regular_price" => "required",
            "sales_price" => "required",
            "product_material" => "string|nullable|max:250",
        ];
    }

    public function message()
    {
        return [
            'category_id.required' => 'Please select a category',
            'sub_category_id.required' => 'Please select a sub category',
            'product_name.required' => 'Product name is required',
            'quantity_supplied.required' => 'Please add the quantity supplied'
        ];
    }
}
