<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BerantasTatBarangBukti extends Model
{
    protected $table = 'berantas_tat_barang_bukti';
    protected $guarded = ['id'];

    public function narkotika() {
        return $this->belongsTo(BerantasNarkotika::class, 'narkotika_id');
    }

    public function getNamaBarangAttribute() {
        if ($this->kategori === 'Narkotika') {
            return $this->narkotika ? $this->narkotika->nama_narkotika : 'Unknown';
        }
        return $this->nama_barang_non_narkotika;
    }

    // Accessor untuk menyatukan logika satuan di View Index
    public function getSatuanAttribute() {
        if ($this->kategori === 'Narkotika') {
            return $this->satuan_narkotika;
        }
        return $this->satuan_non_narkotika;
    }
}