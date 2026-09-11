<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $ledger_id
 * @property string $name
 * @property string|null $color
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['ledger_id', 'name', 'color'])]
class Tag extends Model
{
    /** @return BelongsTo<Ledger, $this> */
    public function ledger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class);
    }

    /** @return HasMany<Taggable, $this> */
    public function taggables(): HasMany
    {
        return $this->hasMany(Taggable::class);
    }

    /** @return MorphToMany<Transaction, $this> */
    public function transactions(): MorphToMany
    {
        return $this->morphedByMany(Transaction::class, 'taggable')->withTimestamps();
    }
}
