<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExperienceRequest extends FormRequest
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
            'year_start' => 'required|date_format:Y-m-d',
            'year_end' => 'date_format:Y-m-d|after_or_equal:year_start',
            'poste' => 'required|string|max:255',
            'place' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'currently_working' => 'required|boolean',

        ];
    }
}
