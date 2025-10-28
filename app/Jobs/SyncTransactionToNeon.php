<?php

namespace App\Jobs;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Job pour synchroniser les transactions vers la base de données Neon
 * Permet un découplage fort avec la base de données historique
 */
class SyncTransactionToNeon implements ShouldQueue
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
            // Configuration de la connexion Neon
            $neonConfig = config('database.neon', [
                'host' => env('NEON_DB_HOST'),
                'port' => env('NEON_DB_PORT', 5432),
                'database' => env('NEON_DB_DATABASE'),
                'username' => env('NEON_DB_USERNAME'),
                'password' => env('NEON_DB_PASSWORD'),
            ]);

            // Données à synchroniser
            $transactionData = [
                'id' => $this->transaction->id,
                'compte_id' => $this->transaction->compte_id,
                'reference' => $this->transaction->reference,
                'type' => $this->transaction->type,
                'montant' => $this->transaction->montant,
                'devise' => $this->transaction->devise,
                'description' => $this->transaction->description,
                'statut' => $this->transaction->statut,
                'date_transaction' => $this->transaction->date_transaction,
                'compte_source_id' => $this->transaction->compte_source_id,
                'compte_destination_id' => $this->transaction->compte_destination_id,
                'created_at' => $this->transaction->created_at,
                'updated_at' => $this->transaction->updated_at,
            ];

            // Utilisation d'une connexion séparée pour Neon
            $neonConnection = DB::connection('neon');

            // Insertion dans la table historique_transactions de Neon
            $neonConnection->table('historique_transactions')->insert($transactionData);

            Log::info('Transaction synchronisée vers Neon', [
                'transaction_id' => $this->transaction->id,
                'reference' => $this->transaction->reference,
                'type' => $this->transaction->type,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la synchronisation vers Neon', [
                'transaction_id' => $this->transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-lancer l'exception pour que le job soit marqué comme échoué
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job SyncTransactionToNeon échoué', [
            'transaction_id' => $this->transaction->id,
            'error' => $exception->getMessage(),
        ]);

        // Ici, on pourrait implémenter une logique de retry ou d'alerte
        // Par exemple, envoyer un email d'alerte ou mettre la transaction en statut "sync_pending"
    }
}
