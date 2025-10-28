<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('comptes', function (Blueprint $table) {
            $table->boolean('is_blocked')->default(false)->after('statut');
            $table->text('motif_blockage')->nullable()->after('is_blocked');
            $table->timestamp('date_debut_blockage')->nullable()->after('motif_blockage');
            $table->timestamp('date_fin_blockage')->nullable()->after('date_debut_blockage');
            $table->text('motif_deblockage')->nullable()->after('date_fin_blockage');
            $table->timestamp('date_deblockage')->nullable()->after('motif_deblockage');
            $table->integer('duree_blockage')->nullable()->after('date_deblockage'); // en jours
            $table->string('unite_blockage', 10)->nullable()->after('duree_blockage'); // jours, semaines, mois
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comptes', function (Blueprint $table) {
            $table->dropColumn([
                'is_blocked',
                'motif_blockage',
                'date_debut_blockage',
                'date_fin_blockage',
                'motif_deblockage',
                'date_deblockage',
                'duree_blockage',
                'unite_blockage',
            ]);
        });
    }
};
