<?php
namespace App\Actions\AdminDinas;
use App\Models\User;
class MaskSensitiveUmkmFields { public function execute(array $payload, User $user): array { if (! $user->hasPermission('umkm.sensitive.financial')) unset($payload['monthly_revenue']); if (! $user->hasPermission('umkm.sensitive.contact')) unset($payload['owner_phone'],$payload['owner_email']); if (! $user->hasPermission('umkm.sensitive.legality')) unset($payload['nib_number'],$payload['oss_risk_level']); if (! $user->hasPermission('umkm.sensitive.coordinate')) unset($payload['latitude'],$payload['longitude']); if (! $user->hasPermission('umkm.sensitive.coaching_note')) unset($payload['coaching_notes']); return $payload; }}
