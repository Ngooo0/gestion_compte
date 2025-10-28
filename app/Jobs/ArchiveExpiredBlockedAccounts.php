<?php

namespace App\Jobs;

use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ArchiveExpiredBlockedAccounts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Début du job d\'archivage des comptes bloqués expirés');

        $now = now();
        $comptesExpires = DB::table('comptes')
            ->where('is_blocked', true)
            ->where('date_fin_blockage', '<=', $now)
            ->where('statut', '!=', 'ferme')
            ->get();

        $totalComptes = $comptesExpires->count();
        $comptesArchives = 0;
        $transactionsArchivees = 0;

        Log::info("Nombre de comptes bloqués expirés trouvés : {$totalComptes}");

        foreach ($comptesExpires as $compte) {
            try {
                DB::transaction(function () use ($compte, &$comptesArchives, &$transactionsArchivees) {
                    // Archiver les transactions du compte
                    $transactionsCount = DB::table('transactions')
                        ->where('compte_id', $compte->id)
                        ->update(['statut' => 'archive']);
                    $transactionsArchivees += $transactionsCount;

                    // Archiver le compte (changement de statut)
                    DB::table('comptes')
                        ->where('id', $compte->id)
                        ->update(['statut' => 'archive']);

                    $comptesArchives++;

                    Log::info('Compte archivé avec succès', [
                        'compte_id' => $compte->id,
                        'numero_compte' => $compte->numero,
                        'date_fin_blockage' => $compte->date_fin_blockage,
                        'transactions_archivees' => $transactionsCount,
                    ]);
                });
            } catch (\Exception $e) {
                Log::error('Erreur lors de l\'archivage du compte', [
                    'compte_id' => $compte->id,
                    'numero_compte' => $compte->numero,
                    'error' => $e->getMessage(),
                ]);

                // Continuer avec les autres comptes même en cas d'erreur
                continue;
            }
        }

        Log::info('Fin du job d\'archivage des comptes bloqués expirés', [
            'total_comptes_trouves' => $totalComptes,
            'comptes_archives' => $comptesArchives,
            'transactions_archivees' => $transactionsArchivees,
        ]);
    }
}
