<?php

namespace App\Http\Requests;

use App\Rules\SenegaleseNciRule;
use App\Rules\SenegalesePhoneRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCompteRequest extends FormRequest
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
            // Au moins un champ doit être fourni
            'titulaire' => 'sometimes|string|max:255',
            'informationsClient' => 'sometimes|array',
            'informationsClient.telephone' => ['sometimes', 'nullable', new SenegalesePhoneRule(), 'unique:clients,telephone,' . $this->route('compte')?->client_id],
            'informationsClient.email' => 'sometimes|nullable|email|unique:clients,email,' . $this->route('compte')?->client_id,
            'informationsClient.password' => 'sometimes|nullable|string|min:8',
            'informationsClient.nci' => ['sometimes', 'nullable', new SenegaleseNciRule()],
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
            // Vérifier qu'au moins un champ est fourni
            $hasTitulaire = $this->has('titulaire');
            $hasClientInfo = $this->has('informationsClient') &&
                           collect($this->input('informationsClient', []))->filter()->isNotEmpty();

            if (!$hasTitulaire && !$hasClientInfo) {
                $validator->errors()->add('general', 'Au moins un champ de modification doit être fourni.');
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
            'titulaire.string' => 'Le nom du titulaire doit être une chaîne de caractères.',
            'titulaire.max' => 'Le nom du titulaire ne peut pas dépasser 255 caractères.',
            'informationsClient.telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'informationsClient.email.email' => 'L\'email doit être une adresse email valide.',
            'informationsClient.email.unique' => 'Cet email est déjà utilisé.',
            'informationsClient.password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'general' => 'Au moins un champ de modification doit être fourni.',
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
            'titulaire' => 'nom du titulaire',
            'informationsClient.telephone' => 'téléphone',
            'informationsClient.email' => 'email',
            'informationsClient.password' => 'mot de passe',
            'informationsClient.nci' => 'numéro NCI',
        ];
    }
}
