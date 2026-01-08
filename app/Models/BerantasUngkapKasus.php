<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class BerantasUngkapKasus extends Model
{
    protected $table = 'berantas_ungkap_kasus';
    protected $guarded = ['id'];
    protected $casts = ['tanggal_kejadian' => 'date'];

    protected static function boot() {
        parent::boot();
        static::deleting(function ($kasus) {
            foreach ($kasus->dokumentasi as $doc) {
                if (Storage::disk('public')->exists($doc->path_file)) {
                    Storage::disk('public')->delete($doc->path_file);
                }
                $doc->delete();
            }
        });
    }

    public function tersangka() { return $this->hasMany(BerantasUngkapTersangka::class, 'berantas_ungkap_kasus_id'); }
    public function barangBukti() { return $this->hasMany(BerantasUngkapBarangBukti::class, 'berantas_ungkap_kasus_id'); }
    public function satuanKerja() { return $this->belongsTo(SatuanKerja::class, 'satuan_kerja_id'); }
    public function dokumentasi() { return $this->morphMany(DokumentasiKegiatan::class, 'dokumentasiable'); }
}