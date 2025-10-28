<?php

namespace App\Jobs;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Job pour envoyer des SMS de notification via Twilio
 * Découplé du processus principal pour éviter les blocages
 */
class SendTransactionSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Transaction $transaction;

    /**
     * Create a new job instance.
     */
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $client = $this->transaction->compte->client;

            // Vérifier que le client a un numéro de téléphone
            if (empty($client->telephone)) {
                Log::warning('Client sans numéro de téléphone pour SMS', [
                    'transaction_id' => $this->transaction->id,
                    'client_id' => $client->id,
                ]);
                return;
            }

            // Préparer le message SMS
            $message = $this->prepareSmsMessage();

            // Configuration Twilio
            $twilioConfig = [
                'sid' => env('TWILIO_ACCOUNT_SID'),
                'token' => env('TWILIO_AUTH_TOKEN'),
                'from' => env('TWILIO_PHONE_NUMBER'),
            ];

            // Vérifier que Twilio est configuré
            if (empty($twilioConfig['sid']) || empty($twilioConfig['token']) || empty($twilioConfig['from'])) {
                Log::warning('Configuration Twilio manquante - SMS non envoyé', [
                    'transaction_id' => $this->transaction->id,
                ]);
                return;
            }

            // Envoyer le SMS via Twilio API
            $response = Http::withBasicAuth($twilioConfig['sid'], $twilioConfig['token'])
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$twilioConfig['sid']}/Messages.json", [
                    'From' => $twilioConfig['from'],
                    'To' => $this->formatPhoneNumber($client->telephone),
                    'Body' => $message,
                ]);

            if ($response->successful()) {
                Log::info('SMS envoyé avec succès', [
                    'transaction_id' => $this->transaction->id,
                    'client_id' => $client->id,
                    'telephone' => $client->telephone,
                    'twilio_sid' => $response->json()['sid'] ?? null,
                ]);
            } else {
                throw new \Exception('Erreur Twilio: ' . $response->body());
            }

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi du SMS', [
                'transaction_id' => $this->transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-lancer l'exception pour que le job soit marqué comme échoué
            throw $e;
        }
    }

    /**
     * Préparer le message SMS selon le type de transaction
     */
    private function prepareSmsMessage(): string
    {
        $montant = number_format($this->transaction->montant, 0, ',', ' ');
        $devise = $this->transaction->devise;
        $reference = $this->transaction->reference;
        $date = $this->transaction->date_transaction->format('d/m/Y H:i');

        switch ($this->transaction->type) {
            case 'depot':
                return "Banque: Dépôt de {$montant} {$devise} effectué. Réf: {$reference}. Date: {$date}.";

            case 'retrait':
                return "Banque: Retrait de {$montant} {$devise} effectué. Réf: {$reference}. Date: {$date}.";

            case 'virement':
                return "Banque: Virement de {$montant} {$devise} effectué. Réf: {$reference}. Date: {$date}.";

            default:
                return "Banque: Transaction de {$montant} {$devise} effectuée. Réf: {$reference}. Date: {$date}.";
        }
    }

    /**
     * Formater le numéro de téléphone pour Twilio
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Supprimer tous les espaces et caractères non numériques
        $phone = preg_replace('/\D/', '', $phone);

        // Si le numéro commence par 221 (Sénégal), ajouter le +
        if (str_starts_with($phone, '221')) {
            return '+' . $phone;
        }

        // Si le numéro commence par 77, 78, 76, 70 (Sénégal), ajouter 221 et +
        if (preg_match('/^(77|78|76|70)/', $phone)) {
            return '+221' . $phone;
        }

        // Pour les autres numéros, ajouter + si nécessaire
        if (!str_starts_with($phone, '+')) {
            return '+' . $phone;
        }

        return $phone;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job SendTransactionSms échoué', [
            'transaction_id' => $this->transaction->id,
            'error' => $exception->getMessage(),
        ]);

        // Ici, on pourrait implémenter une logique de retry ou d'alerte
        // Par exemple, stocker le SMS en attente pour un nouvel essai
    }
}
