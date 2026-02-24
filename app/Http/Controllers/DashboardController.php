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
            return redirect()->route('dashboard.berantas.index');
        }
        
        // 2. Jika admin/operator khusus Rehab
        if (in_array($role, ['admin_rehab', 'operator_rehab'])) {
            return redirect()->route('dashboard.rehab.index');
        }

        // 3. Jika admin/operator khusus P2M
        if (in_array($role, ['admin_p2m', 'operator_p2m'])) {
            return redirect()->route('dashboard.p2m.index');
        }

        // 4. Default (Super Admin, Admin Satker, Operator Satker)
        return redirect()->route('dashboard.p2m.index');
    }
}