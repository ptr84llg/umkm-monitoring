<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\SoftDeletes;use Illuminate\Database\Eloquent\Relations\BelongsTo;use Illuminate\Database\Eloquent\Relations\HasMany;use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class Role extends Model { protected $fillable=['code','name','description','is_active']; public function users(): BelongsToMany { return $this->belongsToMany(User::class, 'user_roles')->withTimestamps(); } public function permissions(): BelongsToMany { return $this->belongsToMany(Permission::class, 'role_permissions')->withTimestamps(); }}

