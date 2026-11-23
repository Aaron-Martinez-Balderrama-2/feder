<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'instructor_id' => ['nullable', 'integer', 'exists:users,id'],
            'hook' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'outcome' => ['required', 'string', 'max:255'],
            'level' => ['required', 'string', 'in:inicial,intermedio,avanzado'],
            'capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'color' => ['nullable', 'string', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'meet_url' => ['nullable', 'url', 'max:255'],
            'whatsapp_url' => ['nullable', 'url', 'max:255'],
            'status' => ['required', 'string', 'in:draft,published,archived'],
            'cover_base64' => ['nullable', 'string'],
        ];
    }
}
