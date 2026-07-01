<?php

declare(strict_types=1);

return [
    'admin_email' => env('LEAD_ADMIN_EMAIL'),
    'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),
    'trial_days' => (int) env('LEAD_TRIAL_DAYS', 7),
];
