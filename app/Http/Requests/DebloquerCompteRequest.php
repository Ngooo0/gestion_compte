<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DebloquerCompteRequest extends FormRequest
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
            'motif' => 'required|string|max:500',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $compte = $this->route('compte');

            // Vérifier que le compte est bloqué
            if (!$compte->is_blocked) {
                $validator->errors()->add('compte', 'Ce compte n\'est pas bloqué.');
            }

            // Vérifier que le compte est de type épargne
            if ($compte->type !== 'epargne') {
                $validator->errors()->add('compte', 'Seuls les comptes épargne peuvent être débloqués.');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'motif.required' => 'Le motif de déblocage est obligatoire.',
            'motif.string' => 'Le motif doit être une chaîne de caractères.',
            'motif.max' => 'Le motif ne peut pas dépasser 500 caractères.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'motif' => 'motif de déblocage',
        ];
    }
}
