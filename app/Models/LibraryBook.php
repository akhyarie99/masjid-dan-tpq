<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LibraryBook extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'masjid_id',
        'title',
        'author',
        'publisher',
        'category',
        'isbn',
        'stock',
        'cover_path',
        'description',
    ];

    public function masjid(): BelongsTo
    {
        return $this->belongsTo(Masjid::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(LibraryLoan::class, 'book_id');
    }

    public function availableStock(): int
    {
        return $this->stock - $this->loans()->where('status', 'dipinjam')->count();
    }
}
