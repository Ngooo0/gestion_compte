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

class UnarchiveExpiredBlockedAccounts implements ShouldQueue
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
        Log::info('Début du job de désarchivage des comptes bloqués expirés');

        $now = now();
        $comptesExpires = DB::table('comptes')
            ->where('is_blocked', true)
            ->where('statut', 'bloque')
            ->where('date_fin_blockage', '<=', $now)
            ->get();

        $totalComptes = $comptesExpires->count();
        $comptesDesarchives = 0;
        $transactionsDesarchivees = 0;

        Log::info("Nombre de comptes bloqués expirés à désarchiver : {$totalComptes}");

        foreach ($comptesExpires as $compte) {
            try {
                DB::transaction(function () use ($compte, $now, &$comptesDesarchives, &$transactionsDesarchivees) {
                    // Désarchiver les transactions du compte (remettre à validee)
                    $transactionsCount = DB::table('transactions')
                        ->where('compte_id', $compte->id)
                        ->where('statut', 'archive')
                        ->update(['statut' => 'validee']);
                    $transactionsDesarchivees += $transactionsCount;

                    // Désarchiver le compte (remettre à actif et débloquer)
                    DB::table('comptes')
                        ->where('id', $compte->id)
                        ->update([
                            'is_blocked' => false,
                            'statut' => 'actif',
                            'motif_deblockage' => 'Désarchivage automatique - Fin de période de blocage',
                            'date_deblockage' => $now,
                        ]);

                    $comptesDesarchives++;

                    Log::info('Compte désarchivé avec succès', [
                        'compte_id' => $compte->id,
                        'numero_compte' => $compte->numero,
                        'date_fin_blockage' => $compte->date_fin_blockage,
                        'date_deblockage' => $now,
                        'transactions_desarchivees' => $transactionsCount,
                    ]);
                });
            } catch (\Exception $e) {
                Log::error('Erreur lors du désarchivage du compte', [
                    'compte_id' => $compte->id,
                    'numero_compte' => $compte->numero,
                    'error' => $e->getMessage(),
                ]);

                // Continuer avec les autres comptes même en cas d'erreur
                continue;
            }
        }

        Log::info('Fin du job de désarchivage des comptes bloqués expirés', [
            'total_comptes_trouves' => $totalComptes,
            'comptes_desarchives' => $comptesDesarchives,
            'transactions_desarchivees' => $transactionsDesarchivees,
        ]);
    }
}
