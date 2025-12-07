<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    const ROLE_ADMIN = 'admin';
    const ROLE_OPERATOR = 'operator';
    
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'pegawai_nip'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // --- LOGIKA DATA SCOPING ---

    /**
     * Relasi: User terhubung ke data Pegawai mana?
     */
    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'pegawai_nip', 'nip');
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isOperator(): bool
    {
        return $this->role === self::ROLE_OPERATOR;
    }

    /**
     * Helper Penting: Ambil ID Satker user ini.
     * - Mengembalikan ID Satker (jika user biasa)
     * - Mengembalikan NULL (jika Super Admin)
     */
    public function getSatkerId()
    {
        return $this->pegawai ? $this->pegawai->satuan_kerja_id : null;
    }

}
