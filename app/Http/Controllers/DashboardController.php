<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $role = $user->role;

        // 1. Jika admin bidang tertentu, langsung lempar ke dashboard bidangnya
        if (in_array($role, ['admin_berantas', 'operator_berantas'])) {
            // Aktifkan jika Dashboard Berantas sudah dibuat:
            // return redirect()->route('dashboard.berantas.index');
        }
        
        if (in_array($role, ['admin_rehab', 'operator_rehab'])) {
            // Aktifkan jika Dashboard Rehab sudah dibuat:
            // return redirect()->route('dashboard.rehab.index');
        }

        // 2. Default untuk admin, admin_satker, operator_satker, admin_p2m, operator_p2m
        // Mereka akan diarahkan ke P2M terlebih dahulu
        return redirect()->route('dashboard.p2m.index');
    }
}