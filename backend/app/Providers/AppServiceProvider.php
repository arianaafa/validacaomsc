<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Msc\MscLineValidator;
use App\Services\Msc\Rules\D1_00017Rule;
use App\Services\Msc\Rules\D1_00021Rule;
use App\Services\Msc\Rules\D1_00025Rule;
use App\Services\Msc\Rules\D1_00027Rule;
use App\Services\Msc\Rules\D1_00028Rule;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MscLineValidator::class, static function (): MscLineValidator {
            return new MscLineValidator([
                new D1_00017Rule(),
                new D1_00021Rule(),
                new D1_00025Rule(),
                new D1_00027Rule(),
                new D1_00028Rule(),
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
