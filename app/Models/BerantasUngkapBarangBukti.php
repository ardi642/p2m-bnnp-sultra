<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BerantasUngkapBarangBukti extends Model
{
    protected $table = 'berantas_ungkap_barang_bukti';
    protected $guarded = ['id'];

    public function narkotika(): BelongsTo 
    {
        return $this->belongsTo(BerantasNarkotika::class, 'narkotika_id');
    }

    public function tersangka(): BelongsToMany 
    {
        return $this->belongsToMany(BerantasUngkapTersangka::class, 'berantas_barang_bukti_tersangka', 'barang_bukti_id', 'tersangka_id');
    }

    // Accessor: Otomatis pilih nama yang sesuai
    public function getNamaBarangAttribute()
    {
        if ($this->kategori === 'Narkotika') {
            return $this->narkotika ? $this->narkotika->nama_narkotika : 'Unknown Narkotika';
        }
        return $this->nama_barang_non_narkotika;
    }

    // Accessor: Otomatis pilih satuan yang sesuai
    public function getSatuanAttribute()
    {
        return $this->kategori === 'Narkotika' ? $this->satuan_narkotika : $this->satuan_non_narkotika;
    }
}