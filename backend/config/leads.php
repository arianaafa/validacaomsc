<?php

declare(strict_types=1);

return [
    'admin_email' => env('LEAD_ADMIN_EMAIL'),
    'trial_hours' => (int) env('LEAD_TRIAL_HOURS', 24),
    'trial_max_uploads' => (int) env('LEAD_TRIAL_MAX_UPLOADS', 1),
];
