<?php
namespace App\Providers;
use App\Models\{Umkm,UmkmLegality,UmkmLocation,UmkmUpdateSubmission};use App\Policies\{UmkmLegalityPolicy,UmkmLocationPolicy,UmkmPolicy,UmkmUpdateSubmissionPolicy};use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
class AuthServiceProvider extends ServiceProvider { protected $policies=[Umkm::class=>UmkmPolicy::class,UmkmUpdateSubmission::class=>UmkmUpdateSubmissionPolicy::class,UmkmLegality::class=>UmkmLegalityPolicy::class,UmkmLocation::class=>UmkmLocationPolicy::class]; public function boot(): void { $this->registerPolicies(); }}
