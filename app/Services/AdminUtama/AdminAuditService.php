<?php
namespace App\Services\AdminUtama;
use App\Services\Audit\AuditLogger;use Illuminate\Http\Request;
class AdminAuditService { public function __construct(private readonly AuditLogger $auditLogger) {} public function logManagementChange(Request $request, string $action, string $targetType, ?int $targetId=null, array $before=[], array $after=[]): void { $this->auditLogger->log($action,$request,$targetType,$targetId,$before,$after); }}
