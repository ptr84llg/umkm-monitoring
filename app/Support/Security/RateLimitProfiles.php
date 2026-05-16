<?php
namespace App\Support\Security;
class RateLimitProfiles { public static function profiles(): array { return ['login'=>'5,1','otp'=>'5,1','reset-password'=>'3,10','survey'=>'20,1','expert-validation'=>'20,1','search'=>'60,1','upload'=>'20,1','export'=>'10,1']; }}
