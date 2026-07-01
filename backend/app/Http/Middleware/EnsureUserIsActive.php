<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Lead\LeadProvisioningService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureUserIsActive
{
    public function __construct(
        private readonly LeadProvisioningService $leadProvisioningService,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user !== null) {
            $this->leadProvisioningService->expireTrialIfNeeded($user);
            $user->refresh();
        }

        if ($user !== null && ! $user->isActive()) {
            abort(Response::HTTP_FORBIDDEN, __('auth.inactive'));
        }

        return $next($request);
    }
}
