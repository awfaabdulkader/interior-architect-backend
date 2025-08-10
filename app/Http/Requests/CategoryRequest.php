<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

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
        if ($this->isMethod('put') || $this->isMethod('patch') || $this->input('_method') === 'PUT') {
            // Get category ID from route parameter
            $categoryId = $this->route('id') ?? $this->route()->parameter('id');

            Log::info('CategoryRequest validation:', [
                'method' => $this->method(),
                'category_id' => $categoryId,
                'route_params' => $this->route()->parameters(),
                'all_input' => $this->all(),
                'has_name' => $this->has('name'),
                'name_value' => $this->input('name'),
                'content_type' => $this->header('Content-Type')
            ]);

            return [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('categories', 'name')->ignore($categoryId, 'id'),
                ],
                'description' => 'nullable|string|max:500',
                'cover' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ];
        }

        return [
            'name' => 'required|array',
            'name.*' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|array',
            'description.*' => 'nullable|string|max:500',
            'cover' => 'nullable|array',
            'cover.*' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];
    }

    /**
     * Custom error messages
     */
    public function messages()
    {
        if ($this->isMethod('put') || $this->isMethod('patch') || $this->input('_method') === 'PUT') {
            return [
                'name.required' => 'Le nom de la catégorie est requis.',
                'name.string' => 'Le nom de la catégorie doit être du texte.',
                'name.max' => 'Le nom de la catégorie ne peut pas dépasser 255 caractères.',
                'name.unique' => 'Ce nom de catégorie existe déjà.',
                'description.string' => 'La description doit être du texte.',
                'description.max' => 'La description ne peut pas dépasser 500 caractères.',
                'cover.image' => 'Le fichier doit être une image.',
                'cover.mimes' => 'L\'image doit être au format JPG, JPEG ou PNG.',
                'cover.max' => 'L\'image ne peut pas dépasser 2MB.',
            ];
        }

        return [
            'name.required' => 'Au moins un nom de catégorie est requis.',
            'name.array' => 'Les noms de catégories doivent être fournis sous forme de tableau.',
            'name.*.required' => 'Chaque nom de catégorie est requis.',
            'name.*.string' => 'Chaque nom de catégorie doit être du texte.',
            'name.*.max' => 'Chaque nom de catégorie ne peut pas dépasser 255 caractères.',
            'name.*.unique' => 'Un ou plusieurs noms de catégories existent déjà.',
            'description.*.string' => 'Chaque description doit être du texte.',
            'description.*.max' => 'Chaque description ne peut pas dépasser 500 caractères.',
            'cover.*.image' => 'Chaque fichier doit être une image.',
            'cover.*.mimes' => 'Chaque image doit être au format JPG, JPEG ou PNG.',
            'cover.*.max' => 'Chaque image ne peut pas dépasser 2MB.',
        ];
    }
}
