<?php
namespace App\Http\Middleware;
use Closure;use Illuminate\Http\Request;
class EnsurePermission { public function handle(Request $request, Closure $next, string ...$permissions) { $user=$request->user(); if(!$user || !$user->is_active) abort(403); foreach($permissions as $permission){ if(!$user->hasPermission($permission)) abort(403); } return $next($request); }}
