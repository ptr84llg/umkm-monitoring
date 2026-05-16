<?php
namespace App\Policies\Concerns;
use App\Models\User;
trait SensitiveFieldGuard { public function canViewSensitiveField(User $user, string $field): bool { $map=['omzet'=>'umkm.sensitive.financial','kontak'=>'umkm.sensitive.contact','legalitas'=>'umkm.sensitive.legality','dokumen'=>'umkm.sensitive.document','koordinat_presisi'=>'umkm.sensitive.coordinate','catatan_pembinaan'=>'umkm.sensitive.coaching_note']; return isset($map[$field]) && $user->hasPermission($map[$field]); }}
