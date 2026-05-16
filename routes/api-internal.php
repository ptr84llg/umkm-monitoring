<?php
use App\Http\Controllers\Api\Internal\{DashboardApiController,InternalApiController,RegionApiController};use Illuminate\Support\Facades\Route;
Route::prefix('internal')->middleware(['auth','throttle:internal-sensitive','validate.internal.origin','validate.internal.referer','validate.fetch.metadata','log.internal.api'])->group(function(){
    Route::get('/dashboard',[InternalApiController::class,'dashboard'])->middleware('permission:dashboard.view.executive');
    Route::get('/map',[InternalApiController::class,'map'])->middleware('permission:umkm.read.official');
    Route::get('/table',[InternalApiController::class,'table'])->middleware('permission:umkm.read.official');
    Route::get('/filter',[InternalApiController::class,'filter'])->middleware('permission:umkm.read.official');
    Route::post('/upload',[InternalApiController::class,'upload'])->middleware('permission:umkm.write.official');
    Route::post('/export',[InternalApiController::class,'export'])->middleware('permission:export.sensitive');
    Route::post('/survey',[InternalApiController::class,'survey'])->middleware('permission:survey.fill');
    Route::post('/expert-validation',[InternalApiController::class,'expertValidation'])->middleware('permission:validation.expert.fill');
    Route::get('/audit',[InternalApiController::class,'audit'])->middleware('permission:audit.read');
    Route::get('/regions',[RegionApiController::class,'children'])->middleware('permission:umkm.read.official');
    Route::get('/dashboard/indicators',[DashboardApiController::class,'indicators'])->middleware('permission:dashboard.view.executive');
    Route::get('/dashboard/charts',[DashboardApiController::class,'charts'])->middleware('permission:dashboard.view.executive');
    Route::get('/dashboard/map',[DashboardApiController::class,'map'])->middleware('permission:dashboard.view.executive');
    Route::get('/dashboard/summary-table',[DashboardApiController::class,'summaryTable'])->middleware('permission:dashboard.view.executive');
});
