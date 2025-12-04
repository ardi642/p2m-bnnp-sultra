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

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'pegawai_nip'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
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

    /**
     * Cek apakah User adalah Super Admin (Pusat)
     * Logic: Admin adalah user yang kolom pegawai_nip-nya KOSONG (NULL)
     */
    public function isSuperAdmin(): bool
    {
        return is_null($this->pegawai_nip);
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
