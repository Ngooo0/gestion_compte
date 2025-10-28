<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'type',
        'montant',
        'devise',
        'description',
        'statut',
        'date_transaction',
        'compte_id',
        'compte_destination_id',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_transaction' => 'datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function setReferenceAttribute($value)
    {
        if (empty($value)) {
            $this->attributes['reference'] = 'TXN-' . strtoupper(Str::random(10)) . '-' . date('Ymd');
        } else {
            $this->attributes['reference'] = $value;
        }
    }

    public function compte(): BelongsTo
    {
        return $this->belongsTo(Compte::class);
    }

    public function compteDestination(): BelongsTo
    {
        return $this->belongsTo(Compte::class, 'compte_destination_id');
    }
}
