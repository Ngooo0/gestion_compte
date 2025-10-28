<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ClientController;
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
Route::get('/user', function (Request $request) {
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
    | Authentification Routes
    |--------------------------------------------------------------------------
    |
    | Routes publiques pour l'authentification OAuth2
    |
    */
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });

    /*
    |--------------------------------------------------------------------------
    | Clients Routes
    |--------------------------------------------------------------------------
    |
    | Routes pour la gestion des clients bancaires
    | - Admin : accès à tous les clients
    | - Client : accès uniquement à son profil
    |
    */
    Route::middleware(['auth:api', 'api.logging'])->group(function () {

        /**
         * @group Clients
         * @description Lister tous les clients (Admin voit tous, Client voit son profil)
         * @queryParam page int Numéro de page (default: 1) Example: 1
         * @queryParam limit int Nombre d'éléments par page (default: 10, max: 100) Example: 10
         * @queryParam search string Recherche par nom, prénom ou email Example: Dupont
         * @queryParam sort string Tri (nom, prenom, email, dateCreation) Example: nom
         * @queryParam order string Ordre (asc, desc) Example: desc
         * @responseFile responses/clients/index.json
         */
        Route::get('/clients', [ClientController::class, 'index'])
            ->name('clients.index');

        /**
         * @group Clients
         * @description Récupérer un client spécifique par ID (Admin voit tous, Client voit son profil)
         * @urlParam client string required ID du client Example: 550e8400-e29b-41d4-a716-446655440000
         * @responseFile responses/clients/show.json
         */
        Route::get('/clients/{client}', [ClientController::class, 'show'])
            ->name('clients.show');

        /**
         * @group Clients
         * @description Lister les comptes d'un client spécifique (Admin voit tous, Client voit ses comptes)
         * @urlParam client string required ID du client Example: 550e8400-e29b-41d4-a716-446655440000
         * @queryParam page int Numéro de page (default: 1) Example: 1
         * @queryParam limit int Nombre d'éléments par page (default: 10, max: 100) Example: 10
         * @queryParam type string Filtrer par type (epargne, cheque) Example: epargne
         * @queryParam statut string Filtrer par statut (actif, bloque, ferme) Example: actif
         * @responseFile responses/clients/comptes.json
         */
        Route::get('/clients/{client}/comptes', [ClientController::class, 'comptes'])
            ->name('clients.comptes');

        /**
         * @group Clients
         * @description Modifier les informations d'un client
         * @urlParam client string required ID du client Example: 550e8400-e29b-41d4-a716-446655440000
         * @bodyParam nom string optional Nouveau nom Example: Diallo
         * @bodyParam prenom string optional Nouveau prénom Example: Amadou Junior
         * @bodyParam email string optional Nouveau email Example: nouveau.email@example.com
         * @bodyParam telephone string optional Nouveau téléphone Example: +221771234568
         * @bodyParam adresse string optional Nouvelle adresse Example: Dakar, Sénégal
         * @bodyParam date_naissance string optional Nouvelle date de naissance Example: 1990-05-15
         * @responseFile responses/clients/update.json
         */
        Route::patch('/clients/{client}', [ClientController::class, 'update'])
            ->name('clients.update');

    });

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
    Route::middleware(['auth:api', 'api.logging'])->group(function () {

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

        /**
         * @group Comptes
         * @description Créer un nouveau compte bancaire
         * @bodyParam type string required Type de compte (cheque, epargne) Example: cheque
         * @bodyParam soldeInitial numeric required Solde initial (min: 10000) Example: 500000
         * @bodyParam devise string required Devise (XOF, EUR, USD) Example: XOF
         * @bodyParam client object required Informations du client
         * @bodyParam client.id integer nullable ID du client existant Example: null
         * @bodyParam client.titulaire string required Nom du titulaire (si nouveau client) Example: Hawa BB Wane
         * @bodyParam client.email string required Email (si nouveau client) Example: cheikh.sy@example.com
         * @bodyParam client.telephone string required Téléphone sénégalais Example: +221771234567
         * @bodyParam client.adresse string required Adresse Example: Dakar, Sénégal
         * @responseFile responses/comptes/store.json
         */
        Route::post('/comptes', [CompteController::class, 'store'])
            ->name('comptes.store');

        /**
         * @group Comptes
         * @description Modifier partiellement les informations d'un compte
         * @urlParam compte string required ID du compte Example: 550e8400-e29b-41d4-a716-446655440000
         * @bodyParam titulaire string optional Nouveau nom du titulaire Example: Amadou Diallo Junior
         * @bodyParam informationsClient object optional Informations client à modifier
         * @bodyParam informationsClient.telephone string optional Nouveau téléphone Example: +221771234568
         * @bodyParam informationsClient.email string optional Nouveau email Example: nouveau.email@example.com
         * @bodyParam informationsClient.password string optional Nouveau mot de passe Example: nouveauMotDePasse123
         * @bodyParam informationsClient.nci string optional Nouveau NCI Example: 1980123456789
         * @responseFile responses/comptes/update.json
         */
        Route::patch('/comptes/{compte}', [CompteController::class, 'update'])
            ->name('comptes.update');

        /**
         * @group Comptes
         * @description Bloquer un compte épargne (administrateur uniquement)
         * @urlParam compte string required ID du compte Example: 550e8400-e29b-41d4-a716-446655440000
         * @bodyParam motif string required Motif de blocage Example: Activité suspecte détectée
         * @bodyParam duree integer required Durée de blocage Example: 30
         * @bodyParam unite string required Unité de temps (jours, semaines, mois) Example: mois
         * @responseFile responses/comptes/bloquer.json
         */
        Route::post('/comptes/{compte}/bloquer', [CompteController::class, 'bloquer'])
            ->name('comptes.bloquer');

        /**
         * @group Comptes
         * @description Débloquer un compte épargne (administrateur uniquement)
         * @urlParam compte string required ID du compte Example: 550e8400-e29b-41d4-a716-446655440000
         * @bodyParam motif string required Motif de déblocage Example: Vérification complétée
         * @responseFile responses/comptes/debloquer.json
         */
        Route::post('/comptes/{compte}/debloquer', [CompteController::class, 'debloquer'])
            ->name('comptes.debloquer');

        /**
         * @group Comptes
         * @description Supprimer un compte (soft delete - administrateur uniquement)
         * @urlParam compte string required ID du compte Example: 550e8400-e29b-41d4-a716-446655440000
         * @responseFile responses/comptes/delete.json
         */
        Route::delete('/comptes/{compte}', [CompteController::class, 'destroy'])
            ->name('comptes.destroy');

    });

});
