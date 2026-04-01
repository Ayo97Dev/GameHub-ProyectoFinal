<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_id' => 'required|exists:game_sessions,id',
            'final_score' => 'required|integer|min:0',
            'duration' => 'required|integer|min:0',
        ];
    }
}
