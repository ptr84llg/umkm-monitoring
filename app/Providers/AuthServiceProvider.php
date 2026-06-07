<?php

namespace App\Providers;

use App\Models\Umkm\Umkm;
use App\Models\Umkm\UmkmLegality;
use App\Models\Umkm\UmkmLocation;
use App\Models\Umkm\UmkmUpdateSubmission;
use App\Policies\Umkm\UmkmLegalityPolicy;
use App\Policies\Umkm\UmkmLocationPolicy;
use App\Policies\Umkm\UmkmPolicy;
use App\Policies\Umkm\UmkmUpdateSubmissionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Umkm::class => UmkmPolicy::class,
        UmkmUpdateSubmission::class => UmkmUpdateSubmissionPolicy::class,
        UmkmLegality::class => UmkmLegalityPolicy::class,
        UmkmLocation::class => UmkmLocationPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
