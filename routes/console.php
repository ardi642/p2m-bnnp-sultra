<?php

use Illuminate\Support\Facades\Schedule;

// Jalankan setiap hari pukul 00:00 (tengah malam)
Schedule::command('app:clean-temp')->daily();