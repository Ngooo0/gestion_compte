<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Compte extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero',
        'type',
        'solde',
        'devise',
        'statut',
        'client_id',
    ];

    protected $casts = [
        'solde' => 'decimal:2',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function setNumeroAttribute($value)
    {
        if (empty($value)) {
            $this->attributes['numero'] = 'CPT-' . strtoupper(Str::random(8)) . '-' . date('Y');
        } else {
            $this->attributes['numero'] = $value;
        }
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function transactionsEntrantes(): HasMany
    {
        return $this->hasMany(Transaction::class, 'compte_destination_id');
    }

    /**
     * Scope global pour récupérer uniquement les comptes actifs (non supprimés)
     */
    protected static function booted()
    {
        static::addGlobalScope('active', function (Builder $builder) {
            $builder->where('statut', '!=', 'ferme');
        });
    }

    /**
     * Scope local pour récupérer un compte par numéro
     */
    public function scopeNumero(Builder $query, string $numero): Builder
    {
        return $query->where('numero', $numero);
    }

    /**
     * Scope local pour récupérer les comptes d'un client basé sur son téléphone
     */
    public function scopeClient(Builder $query, string $telephone): Builder
    {
        return $query->whereHas('client', function (Builder $q) use ($telephone) {
            $q->where('telephone', $telephone);
        });
    }
}
