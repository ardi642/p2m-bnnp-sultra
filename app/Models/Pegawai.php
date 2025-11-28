<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pegawai extends Model
{
    protected $table = 'pegawai';
    protected $primaryKey = 'nip';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $guarded = [];
}
