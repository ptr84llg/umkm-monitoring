<?php
namespace App\Http\Middleware;
use Closure;use Illuminate\Http\Request;use Throwable;
class SafeErrorResponder { public function handle(Request $request, Closure $next){ try{return $next($request);}catch(Throwable $e){ report($e); return response()->json(['message'=>'Terjadi kesalahan pada sistem.'],500); } }}
