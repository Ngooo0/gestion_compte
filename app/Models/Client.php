<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'telephone',
        'adresse',
        'date_naissance',
        'user_id',
    ];

    protected $casts = [
        'date_naissance' => 'date',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
