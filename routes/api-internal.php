<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Temporarily Disabled Internal API Routes
|--------------------------------------------------------------------------
|
| Scope-2A disables internal API routes until their related modules are
| explicitly reopened and implemented in the approved batch sequence.
|
| Public landing AJAX endpoints remain in routes/web.php because they require
| same-origin/same-site behavior, CSRF/session context, Origin/Referer guard,
| Fetch Metadata validation, internal request guard, throttling, and audit log.
|
| Do not add survey, export, expert validation, internal dashboard, table, map,
| upload, or other internal endpoints here until the related module batch is
| approved.
|
*/
