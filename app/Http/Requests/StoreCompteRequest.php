<?php

namespace App\Http\Requests;

use App\Rules\SenegaleseNciRule;
use App\Rules\SenegalesePhoneRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCompteRequest extends FormRequest
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
            'type' => 'required|in:cheque,epargne',
            'soldeInitial' => 'required|numeric|min:10000',
            'devise' => 'required|string|size:3|in:XOF,EUR,USD',
            'solde' => 'numeric|min:0', // Peut être calculé automatiquement
            'client' => 'required|array',
            'client.id' => 'nullable|exists:clients,id',
            'client.titulaire' => 'required_if:client.id,null|string|max:255',
            'client.nci' => ['nullable', 'string', new SenegaleseNciRule()],
            'client.email' => 'required_if:client.id,null|email|unique:clients,email',
            'client.telephone' => ['required_if:client.id,null', 'string', new SenegalesePhoneRule(), 'unique:clients,telephone'],
            'client.adresse' => 'required_if:client.id,null|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Le type de compte est obligatoire.',
            'type.in' => 'Le type de compte doit être cheque ou epargne.',
            'soldeInitial.required' => 'Le solde initial est obligatoire.',
            'soldeInitial.numeric' => 'Le solde initial doit être un nombre.',
            'soldeInitial.min' => 'Le solde initial doit être d\'au moins 10 000 FCFA.',
            'devise.required' => 'La devise est obligatoire.',
            'devise.size' => 'La devise doit contenir exactement 3 caractères.',
            'devise.in' => 'La devise doit être XOF, EUR ou USD.',
            'client.required' => 'Les informations du client sont obligatoires.',
            'client.array' => 'Les informations du client doivent être un objet.',
            'client.titulaire.required_if' => 'Le nom du titulaire est obligatoire pour un nouveau client.',
            'client.titulaire.string' => 'Le nom du titulaire doit être une chaîne de caractères.',
            'client.titulaire.max' => 'Le nom du titulaire ne peut pas dépasser 255 caractères.',
            'client.email.required_if' => 'L\'email est obligatoire pour un nouveau client.',
            'client.email.email' => 'L\'email doit être une adresse email valide.',
            'client.email.unique' => 'Cet email est déjà utilisé.',
            'client.telephone.required_if' => 'Le téléphone est obligatoire pour un nouveau client.',
            'client.telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'client.adresse.required_if' => 'L\'adresse est obligatoire pour un nouveau client.',
            'client.adresse.string' => 'L\'adresse doit être une chaîne de caractères.',
            'client.adresse.max' => 'L\'adresse ne peut pas dépasser 500 caractères.',
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
            'type' => 'type de compte',
            'soldeInitial' => 'solde initial',
            'devise' => 'devise',
            'client.titulaire' => 'nom du titulaire',
            'client.nci' => 'numéro NCI',
            'client.email' => 'email',
            'client.telephone' => 'téléphone',
            'client.adresse' => 'adresse',
        ];
    }
}
