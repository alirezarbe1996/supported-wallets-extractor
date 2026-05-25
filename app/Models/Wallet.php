<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Wallet extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'enName',
        'faName',
        'icon',
        'website',
    ];

    /**
     * The currencies that belong to the wallet.
     */
    public function currencies(): BelongsToMany
    {
        return $this->belongsToMany(Currency::class);
    }
}
