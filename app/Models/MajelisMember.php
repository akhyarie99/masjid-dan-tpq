<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MajelisMember extends Model
{
    use HasUuids;

    protected $fillable = [
        'majelis_id',
        'name',
        'phone',
        'address',
        'joined_date',
    ];

    protected function casts(): array
    {
        return [
            'joined_date' => 'date',
        ];
    }

    public function majelis(): BelongsTo
    {
        return $this->belongsTo(Majelis::class);
    }
}
