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
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'pegawai_nip',
        
        // --- TAMBAHAN KOLOM BARU (WAJIB ADA) ---
        'is_password_default',            // Agar status password default bisa diupdate
        'pending_email',                  // Agar email sementara bisa disimpan/dihapus
        'verification_token',             // Token verifikasi
        'verification_token_expires_at',  // Waktu expired token
        'email_verified_at',              // Waktu verifikasi
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
            // Casting untuk kolom baru (Opsional tapi bagus)
            'verification_token_expires_at' => 'datetime',
            'is_password_default' => 'boolean',
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

    public function hasRole(string|array $roles): bool
    {
        if (is_array($roles)) {
            return in_array($this->role, $roles);
        }
        return $this->role === $roles;
    }

    // Cek apakah user ini boleh mengelola user lain?
    public function canManageUsers()
    {
        return in_array($this->role, ['admin', 'admin_satker']);
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