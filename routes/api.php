<?PHP
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});
