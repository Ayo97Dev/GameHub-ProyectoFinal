<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatRequest extends FormRequest
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
            'high_score' => ['nullable', 'integer', 'min:0'],
            'time_played' => ['nullable', 'integer', 'min:0'],
            'wins' => ['nullable', 'integer', 'min:0'],
            'draws' => ['nullable', 'integer', 'min:0'],
            'losses' => ['nullable', 'integer', 'min:0'],
            'wins_without_queen_loss' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
