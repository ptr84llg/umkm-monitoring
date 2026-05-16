<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
class User extends Authenticatable
{
    use Notifiable;
    protected $fillable = ['name','email','password','account_type','is_active','last_login_at','google_id','google_linked_at'];
    protected $hidden = ['password','remember_token'];
    protected function casts(): array { return ['is_active'=>'boolean','last_login_at'=>'datetime','google_linked_at'=>'datetime','password'=>'hashed']; }
    public function roles(): BelongsToMany { return $this->belongsToMany(Role::class, 'user_roles')->withTimestamps(); }
    public function hasRole(string $role): bool { return $this->roles()->where('code', $role)->exists(); }
    public function hasPermission(string $permission): bool { return $this->roles()->whereHas('permissions', fn($q) => $q->where('code', $permission))->exists(); }
}
