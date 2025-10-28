<?php

use App\Http\Controllers\Api\V1\CompteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Routes publiques
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
|
| Routes versionnées pour l'API v1 avec authentification Passport
|
*/
Route::prefix('v1')->name('api.v1.')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Comptes Routes
    |--------------------------------------------------------------------------
    |
    | Routes pour la gestion des comptes bancaires
    | - Admin : accès à tous les comptes
    | - Client : accès uniquement à ses comptes
    |
    */
    Route::middleware(['auth:api', 'rating', 'cloud.archive'])->group(function () {

        /**
         * @group Comptes
         * @description Lister tous les comptes (Admin voit tous, Client voit les siens)
         * @queryParam page int Numéro de page (default: 1) Example: 1
         * @queryParam limit int Nombre d'éléments par page (default: 10, max: 100) Example: 10
         * @queryParam type string Filtrer par type (epargne, cheque) Example: epargne
         * @queryParam statut string Filtrer par statut (actif, bloque, ferme) Example: actif
         * @queryParam search string Recherche par titulaire ou numéro Example: Dupont
         * @queryParam sort string Tri (dateCreation, solde, titulaire) Example: dateCreation
         * @queryParam order string Ordre (asc, desc) Example: desc
         * @responseFile responses/comptes/index.json
         */
        Route::get('/comptes', [CompteController::class, 'index'])
            ->name('comptes.index');

        /**
         * @group Comptes
         * @description Récupérer un compte spécifique par ID (Admin voit tous, Client voit les siens)
         * @urlParam compte string required ID du compte Example: 550e8400-e29b-41d4-a716-446655440000
         * @responseFile responses/comptes/show.json
         */
        Route::get('/comptes/{compte}', [CompteController::class, 'show'])
            ->name('comptes.show');

    });

});
