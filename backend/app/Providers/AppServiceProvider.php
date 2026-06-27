<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Msc\Clients\SiconfiClient;
use App\Services\Msc\MscLineValidator;
use App\Services\Msc\Rules\D1_00017Rule;
use App\Services\Msc\Rules\D1_00021Rule;
use App\Services\Msc\Rules\D1_00025Rule;
use App\Services\Msc\Rules\D1_00027Rule;
use App\Services\Msc\Rules\D1_00028Rule;
use App\Services\Msc\Rules\D1_ContinuidadeSaldoRule;
use App\Services\Msc\Rules\D1_PatrimonialContinuidadeRule;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SiconfiClient::class);

        $this->app->singleton(D1_ContinuidadeSaldoRule::class, static function ($app): D1_ContinuidadeSaldoRule {
            return new D1_ContinuidadeSaldoRule(
                $app->make(SiconfiClient::class),
            );
        });

        $this->app->singleton(D1_PatrimonialContinuidadeRule::class, static function ($app): D1_PatrimonialContinuidadeRule {
            return new D1_PatrimonialContinuidadeRule(
                $app->make(SiconfiClient::class),
            );
        });

        $this->app->singleton(MscLineValidator::class, static function ($app): MscLineValidator {
            return new MscLineValidator([
                new D1_00017Rule(),
                new D1_00021Rule(),
                new D1_00025Rule(),
                new D1_00027Rule(),
                new D1_00028Rule(),
                $app->make(D1_PatrimonialContinuidadeRule::class),
                $app->make(D1_ContinuidadeSaldoRule::class),
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
