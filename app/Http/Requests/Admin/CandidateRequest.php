<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi kandidat terpusat (gantikan validasi inline di controller).
 * Aturan foto ketat: cegah pixel bomb / executable (P2-04).
 */
class CandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Otorisasi detail dilakukan di controller (Gate + status + scope).
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'running_mate_name' => 'nullable|string|max:255',
            'candidate_number' => 'required|integer|min:1',
            'vision' => 'nullable|string|max:5000',
            'mission' => 'nullable|string|max:5000',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=2000,max_height=2000',
            'running_mate_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=2000,max_height=2000',
        ];
    }
}
