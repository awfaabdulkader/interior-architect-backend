<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
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
     public function rules()
    {
        // Check if this is an update request (PUT/PATCH) or create request (POST)
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            // Update validation - name as string
            return [
                'name' => 'required|string|max:255|unique:categories,name,' . $this->route('id'),
                'description' => 'nullable|string|max:500',
                'cover' => 'nullable|image|max:2048', // max 2MB
            ];
        }
        
        // Create validation - name as array
        return [
            'name' => 'required|array',
            'name.*' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|array',
        'description.*' => 'nullable|string|max:500',
        'cover' => 'nullable|array',
        'cover.*' => 'nullable|image|max:2048',
        ];
    }

    public function messages()
    {
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            return [
                'name.required' => 'Category name is required.',
                'name.unique' => 'This category name already exists.'
            ];
        }
        
        return [
            'name.required' => 'At least one category name is required.',
            'name.array' => 'Category names must be provided as an array.',
            'name.*.required' => 'Each category name is required.',
            'name.*.unique' => 'One or more category names already exist.'
        ];
    }
}

