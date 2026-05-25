<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Currency extends Model
{
    use HasFactory;

    /**
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'symbol',
        'slug',
    ];

    public function wallets(): BelongsToMany
    {
        return $this->belongsToMany(Wallet::class);
    }
}
