<?php
namespace Tests\Feature;
use App\Http\Controllers\Export\ExportController;use App\Http\Controllers\Survey\SurveyController;use App\Http\Controllers\ValidasiAhli\ExpertValidationController;use App\Services\{Dashboard\DashboardAnalyticsService,Export\ReportExportService,Proposal\UmkmProposalService,ValidasiAhli\ExpertValidationService};use Tests\TestCase;
class WorkflowModulesTest extends TestCase { public function test_workflow_classes_exist(): void { foreach([ExportController::class,ReportExportService::class,UmkmProposalService::class,SurveyController::class,ExpertValidationController::class,ExpertValidationService::class,DashboardAnalyticsService::class] as $class){ $this->assertTrue(class_exists($class), $class); } }}
