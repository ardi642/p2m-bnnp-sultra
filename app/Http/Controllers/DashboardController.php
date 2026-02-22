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

        // 1. Jika admin/operator khusus Berantas
        if (in_array($role, ['admin_berantas', 'operator_berantas'])) {
            // Aktifkan jika Dashboard Berantas sudah dibuat:
            // return redirect()->route('dashboard.berantas.index');
        }
        
        // 2. Jika admin/operator khusus Rehab
        if (in_array($role, ['admin_rehab', 'operator_rehab'])) {
            // Aktifkan jika Dashboard Rehab sudah dibuat:
            // return redirect()->route('dashboard.rehab.index');
        }

        // 3. Jika admin/operator khusus P2M
        if (in_array($role, ['admin_p2m', 'operator_p2m'])) {
            return redirect()->route('dashboard.p2m.index');
        }

        // 4. Default untuk: admin (Super), admin_satker, dan operator_satker
        // Karena mereka punya hak melihat semua tab, kita lempar ke P2M sebagai tab pembuka (Home)
        return redirect()->route('dashboard.p2m.index');
    }
}