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
        // Supprimer les contraintes de clé étrangère qui référencent users.id
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        // Changer le type de la colonne user_id dans clients d'abord
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
        });

        // Changer le type de la colonne id dans users
        Schema::table('users', function (Blueprint $table) {
            $table->dropPrimary('users_pkey');
            $table->dropColumn('id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->bigIncrements('id')->first();
        });

        // Recréer les contraintes de clé étrangère
        Schema::table('clients', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer les contraintes de clé étrangère
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        // Revenir au type UUID
        Schema::table('users', function (Blueprint $table) {
            $table->dropPrimary('users_pkey');
            $table->uuid('id')->primary()->change();
        });

        // Recréer les contraintes de clé étrangère
        Schema::table('clients', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users');
        });
    }
};
