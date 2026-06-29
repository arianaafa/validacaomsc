<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Msc\Clients\SiconfiClient;
use App\Services\Msc\MscLineValidator;
use App\Services\Msc\Rules\D1_00017Rule;
use App\Services\Msc\Rules\D1_00018Rule;
use App\Services\Msc\Rules\D1_00021Rule;
use App\Services\Msc\Rules\D1_00025Rule;
use App\Services\Msc\Rules\D1_00026Rule;
use App\Services\Msc\Rules\D1_00027Rule;
use App\Services\Msc\Rules\D1_00028Rule;
use App\Services\Msc\Rules\D1_00029Rule;
use App\Services\Msc\Rules\D1_00030Rule;
use App\Services\Msc\Rules\D1_ContinuidadeSaldoRule;
use App\Services\Msc\Rules\D1_ControleContinuidadeRule;
use App\Services\Msc\Rules\D1_OrcamentariaContinuidadeRule;
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

        $this->app->singleton(D1_00018Rule::class, static fn (): D1_00018Rule => new D1_00018Rule());

        $this->app->singleton(D1_00028Rule::class, static fn (): D1_00028Rule => new D1_00028Rule());

        $this->app->singleton(D1_ContinuidadeSaldoRule::class, static fn (): D1_ContinuidadeSaldoRule => new D1_ContinuidadeSaldoRule());

        $this->app->singleton(D1_PatrimonialContinuidadeRule::class, static function ($app): D1_PatrimonialContinuidadeRule {
            return new D1_PatrimonialContinuidadeRule(
                $app->make(SiconfiClient::class),
            );
        });

        $this->app->singleton(D1_OrcamentariaContinuidadeRule::class, static function ($app): D1_OrcamentariaContinuidadeRule {
            return new D1_OrcamentariaContinuidadeRule(
                $app->make(SiconfiClient::class),
            );
        });

        $this->app->singleton(D1_ControleContinuidadeRule::class, static function ($app): D1_ControleContinuidadeRule {
            return new D1_ControleContinuidadeRule(
                $app->make(SiconfiClient::class),
            );
        });

        $this->app->singleton(MscLineValidator::class, static function ($app): MscLineValidator {
            return new MscLineValidator([
                new D1_00017Rule(),
                new D1_00021Rule(),
                new D1_00025Rule(),
                new D1_00026Rule(),
                new D1_00027Rule(),
                new D1_00029Rule(),
                new D1_00030Rule(),
                $app->make(D1_00028Rule::class),
                $app->make(D1_00018Rule::class),
                $app->make(D1_PatrimonialContinuidadeRule::class),
                $app->make(D1_OrcamentariaContinuidadeRule::class),
                $app->make(D1_ControleContinuidadeRule::class),
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
