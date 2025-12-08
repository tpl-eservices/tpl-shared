<?php

use App\Http\Controllers\StacksController;
use Illuminate\Support\Facades\Route;

/*
 * Protected Routes - Require BiblioCommons Authentication
 *
 * These routes use the 'biblio.auth' middleware which:
 * 1. Checks for bc_session cookie
 * 2. Validates session with BiblioCommons API
 * 3. Fetches user data from API
 * 4. Creates transient User object
 * 5. Redirects to BiblioCommons login if not authenticated
 */
Route::middleware('biblio.auth')->group(function () {
    Route::get('/', [StacksController::class, 'index'])->name('home');
    Route::post('/', [StacksController::class, 'store'])->name('stacks.store');
});

/*
 * Optional: Debug Routes (Remove in production)
 */
// Route::get('/debug-cookie', function () {
//     return [
//         'bc_session' => getRawCookie('bc_session'),
//         'all_cookies' => array_keys($_COOKIE),
//     ];
// });

// Route::get('/debug-auth', function () {
//     return [
//         'is_authenticated' => Auth::guard('biblio')->check(),
//         'user' => Auth::guard('biblio')->user(),
//     ];
// });
