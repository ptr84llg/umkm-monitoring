<?php
namespace App\Http\Middleware;
use Closure;use Illuminate\Http\Request;
class EnsureRole { public function handle(Request $request, Closure $next, string ...$roles) { $user=$request->user(); if(!$user || !$user->is_active) abort(403); if($roles !== [] && !collect($roles)->contains(fn($r)=>$user->hasRole($r))) abort(403); return $next($request); }}
