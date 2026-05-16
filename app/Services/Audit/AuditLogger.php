<?php
namespace App\Services\Audit;
use App\Models\AuditLog;use Illuminate\Http\Request;
class AuditLogger { public function log(string $action, ?Request $request=null, ?string $targetType=null, ?int $targetId=null, array $before=[], array $after=[]): void { AuditLog::query()->create(['actor_user_id'=>$request?->user()?->id,'action'=>$action,'target_type'=>$targetType,'target_id'=>$targetId,'before_data'=>$before ?: null,'after_data'=>$after ?: null,'ip_address'=>$request?->ip(),'user_agent'=>$request?->userAgent(),'event_time'=>now()]); }}
