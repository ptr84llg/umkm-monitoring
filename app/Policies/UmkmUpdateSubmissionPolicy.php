<?php
namespace App\Policies;
use App\Models\UmkmUpdateSubmission;use App\Models\User;
class UmkmUpdateSubmissionPolicy { public function view(User $user, UmkmUpdateSubmission $submission): bool { return $user->hasPermission('umkm.review.update') || $submission->submitted_by === $user->id; } public function create(User $user): bool { return $user->hasPermission('umkm.submit.update'); } public function review(User $user): bool { return $user->hasPermission('umkm.review.update'); }}
