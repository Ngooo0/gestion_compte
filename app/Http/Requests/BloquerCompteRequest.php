<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BloquerCompteRequest extends FormRequest
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
            'duree' => 'required|integer|min:1|max:365',
            'unite' => 'required|string|in:jours,semaines,mois',
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

            // Vérifier que le compte est de type épargne
            if ($compte->type !== 'epargne') {
                $validator->errors()->add('compte', 'Seuls les comptes épargne peuvent être bloqués.');
            }

            // Vérifier que le compte est actif
            if ($compte->statut !== 'actif') {
                $validator->errors()->add('compte', 'Seul un compte actif peut être bloqué.');
            }

            // Vérifier que le compte n'est pas déjà bloqué
            if ($compte->is_blocked) {
                $validator->errors()->add('compte', 'Ce compte est déjà bloqué.');
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
            'motif.required' => 'Le motif de blocage est obligatoire.',
            'motif.string' => 'Le motif doit être une chaîne de caractères.',
            'motif.max' => 'Le motif ne peut pas dépasser 500 caractères.',
            'duree.required' => 'La durée de blocage est obligatoire.',
            'duree.integer' => 'La durée doit être un nombre entier.',
            'duree.min' => 'La durée minimale est de 1 jour.',
            'duree.max' => 'La durée maximale est de 365 jours.',
            'unite.required' => 'L\'unité de temps est obligatoire.',
            'unite.string' => 'L\'unité doit être une chaîne de caractères.',
            'unite.in' => 'L\'unité doit être jours, semaines ou mois.',
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
            'motif' => 'motif de blocage',
            'duree' => 'durée de blocage',
            'unite' => 'unité de temps',
        ];
    }
}
