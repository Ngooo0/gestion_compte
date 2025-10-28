<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

/**
 * @OA\Info(
 *     title="API Gestion de Comptes - Banque",
 *     version="1.0.0",
 *     description="API REST pour la gestion des comptes bancaires avec authentification OAuth2"
 * )
 *
 * @OA\Server(
 *     url="http://api.banque.example.com/api/v1",
 *     description="Serveur de production"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="passport",
 *     type="oauth2",
 *     description="Laravel Passport OAuth2 security",
 *     @OA\Flow(
 *         flow="password",
 *         tokenUrl="/oauth/token",
 *         refreshUrl="/oauth/token/refresh",
 *         scopes={
 *             "read-comptes": "Lire les comptes",
 *             "write-comptes": "Modifier les comptes",
 *             "admin": "Accès administrateur"
 *         }
 *     )
 * )
 */
class InfoController extends Controller
{
    // Controller pour les annotations Swagger globales
}