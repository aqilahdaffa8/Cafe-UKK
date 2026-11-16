    <?php

    use App\Http\Controllers\Auth\WebLoginController;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\Auth\PasswordController;

    // Halaman awal: arahkan sesuai kondisi login
    Route::get('/', function () {
        $user = Auth()->user();

        if (! $user) {
            return redirect()->route('login');
        }

        return redirect($user->isManajer() ? '/manajer' : '/kasir');
    });

    // Hanya untuk yang BELUM login
    Route::middleware('guest')->group(function () {
        Route::get('/login', [WebLoginController::class, 'show'])->name('login');
        Route::post('/login', [WebLoginController::class, 'store'])->middleware('throttle:5,1');
    });

    // Hanya untuk yang SUDAH login
    Route::middleware('auth')->group(function () {
        Route::post('/logout', [WebLoginController::class, 'destroy'])->name('logout');
        Route::get('/ganti-password', [PasswordController::class, 'edit'])->name('password.edit');
        Route::put('/ganti-password', [PasswordController::class, 'update'])->name('password.update');
    });

    Route::middleware(['auth', 'password.changed', 'role:manajer'])->get('/manajer', function () {
        return view('dashboard_sementara', ['judul' => 'Dashboard Manajer']);
    });

    Route::middleware(['auth', 'password.changed', 'role:kasir'])->get('/kasir', function () {
        return view('dashboard_sementara', ['judul' => 'Dashboard Kasir']);
    });