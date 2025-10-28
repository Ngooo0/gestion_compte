<?php

namespace App\Listeners;

use App\Events\ClientCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Listener pour envoyer les notifications lors de la création d'un client
 * Envoie un email avec les identifiants et un SMS avec le code de vérification
 */
class SendClientNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The name of the queue the job should be sent to.
     */
    public string $queue = 'notifications';

    /**
     * Handle the event.
     *
     * @param ClientCreated $event
     * @return void
     */
    public function handle(ClientCreated $event): void
    {
        $client = $event->client;
        $password = $event->generatedPassword;
        $code = $event->verificationCode;

        // Envoi de l'email avec les identifiants
        $this->sendWelcomeEmail($client, $password, $code);

        // Envoi du SMS avec le code de vérification
        $this->sendVerificationSMS($client, $code);

        // Log de l'envoi des notifications
        Log::info('Notifications envoyées pour nouveau client', [
            'client_id' => $client->id,
            'email' => $client->email,
            'telephone' => $client->telephone,
            'code_sent' => true,
        ]);
    }

    /**
     * Envoi de l'email de bienvenue avec les identifiants
     *
     * @param mixed $client
     * @param string $password
     * @param string $code
     * @return void
     */
    private function sendWelcomeEmail($client, string $password, string $code): void
    {
        try {
            // Simulation d'envoi d'email (remplacer par vraie implémentation)
            Log::info('Email de bienvenue envoyé', [
                'to' => $client->email,
                'subject' => 'Bienvenue chez Gestion Compte',
                'client_name' => $client->nom . ' ' . $client->prenom,
                'login_email' => $client->email,
                'temporary_password' => $password,
                'verification_code' => $code,
            ]);

            // Ici vous pouvez utiliser Mail::to() pour envoyer un vrai email
            // Mail::to($client->email)->send(new WelcomeEmail($client, $password, $code));

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'email de bienvenue', [
                'client_id' => $client->id,
                'email' => $client->email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Envoi du SMS avec le code de vérification
     *
     * @param mixed $client
     * @param string $code
     * @return void
     */
    private function sendVerificationSMS($client, string $code): void
    {
        try {
            // Simulation d'envoi de SMS (remplacer par vraie implémentation)
            Log::info('SMS de vérification envoyé', [
                'to' => $client->telephone,
                'message' => "Votre code de vérification Gestion Compte : {$code}",
                'client_name' => $client->nom . ' ' . $client->prenom,
            ]);

            // Ici vous pouvez intégrer un service SMS comme Twilio, Africa's Talking, etc.
            // $smsService->send($client->telephone, "Votre code : {$code}");

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi du SMS de vérification', [
                'client_id' => $client->id,
                'telephone' => $client->telephone,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     *
     * @param ClientCreated $event
     * @param \Throwable $exception
     * @return void
     */
    public function failed(ClientCreated $event, \Throwable $exception): void
    {
        Log::error('Échec de l\'envoi des notifications client', [
            'client_id' => $event->client->id,
            'exception' => $exception->getMessage(),
        ]);
    }
}
