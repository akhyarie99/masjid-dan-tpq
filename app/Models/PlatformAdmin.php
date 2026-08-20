<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Akun pemilik platform SaaS, terpisah dari `users` (staf per-tenant) — sama
 * seperti WaliAccount terpisah dari `users` karena tidak terikat satu
 * masjid_id manapun. Hanya bisa login dari domain pusat (routes/central.php).
 */
class PlatformAdmin extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
