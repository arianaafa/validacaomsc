<?php

declare(strict_types=1);

return [
    'admin_email' => env('LEAD_ADMIN_EMAIL'),
    'trial_days' => (int) env('LEAD_TRIAL_DAYS', 7),
];
