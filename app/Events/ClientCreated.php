<?php

namespace App\Events;

use App\Models\Client;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event déclenché lorsqu'un nouveau client est créé
 * Utilisé pour envoyer les notifications (email et SMS)
 */
class ClientCreated
{
    use Dispatchable, SerializesModels;

    public Client $client;
    public string $generatedPassword;
    public string $verificationCode;

    /**
     * Create a new event instance.
     *
     * @param Client $client Le client créé
     * @param string $generatedPassword Mot de passe généré
     * @param string $verificationCode Code de vérification généré
     */
    public function __construct(Client $client, string $generatedPassword, string $verificationCode)
    {
        $this->client = $client;
        $this->generatedPassword = $generatedPassword;
        $this->verificationCode = $verificationCode;
    }
}
