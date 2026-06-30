<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Msc\Clients\SiconfiClient;
use App\Services\Msc\MscLineValidator;
use App\Services\Msc\Rules\D1_00001Rule;
use App\Services\Msc\Rules\D1_00002Rule;
use App\Services\Msc\Rules\D1_00003Rule;
use App\Services\Msc\Rules\D1_00006Rule;
use App\Services\Msc\Rules\D2_00001Rule;
use App\Services\Msc\Rules\D2_00002Rule;
use App\Services\Msc\Rules\D1_00017Rule;
use App\Services\Msc\Rules\D1_00018Rule;
use App\Services\Msc\Rules\D1_00021Rule;
use App\Services\Msc\Rules\D1_00034Rule;
use App\Services\Msc\Rules\D1_00035Rule;
use App\Services\Msc\Rules\D1_00036Rule;
use App\Services\Msc\Rules\D1_00037Rule;
use App\Services\Msc\Rules\D1_00038Rule;
use App\Services\Msc\Rules\D1_00039Rule;
use App\Services\Msc\Rules\D1_00040Rule;
use App\Services\Msc\Rules\D1_00041Rule;
use App\Services\Msc\Rules\D1_00042Rule;
use App\Services\Msc\Rules\D1_00043Rule;
use App\Services\Msc\Rules\D1_00025Rule;
use App\Services\Msc\Rules\D1_00026Rule;
use App\Services\Msc\Rules\D1_00027Rule;
use App\Services\Msc\Rules\D1_00028Rule;
use App\Services\Msc\Rules\D1_00029Rule;
use App\Services\Msc\Rules\D1_00030Rule;
use App\Services\Msc\Rules\D1_00031Rule;
use App\Services\Msc\Rules\D1_00032Rule;
use App\Services\Msc\Rules\D1_00033Rule;
use App\Services\Msc\Rules\D1_00044Rule;
// use App\Services\Msc\Rules\D1_ContinuidadeSaldoRule; // Desativado: coberto pela D1_00018 (agrupamento PCASP 01–09).
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

        $this->app->singleton(D1_00001Rule::class, static function ($app): D1_00001Rule {
            return new D1_00001Rule(
                $app->make(SiconfiClient::class),
            );
        });

        $this->app->singleton(D1_00002Rule::class, static function ($app): D1_00002Rule {
            return new D1_00002Rule(
                $app->make(SiconfiClient::class),
            );
        });

        $this->app->singleton(D1_00003Rule::class, static function ($app): D1_00003Rule {
            return new D1_00003Rule(
                $app->make(SiconfiClient::class),
            );
        });

        $this->app->singleton(D1_00006Rule::class, static function ($app): D1_00006Rule {
            return new D1_00006Rule(
                $app->make(SiconfiClient::class),
            );
        });

        $this->app->singleton(D2_00001Rule::class, static function ($app): D2_00001Rule {
            return new D2_00001Rule(
                $app->make(SiconfiClient::class),
            );
        });

        $this->app->singleton(D2_00002Rule::class, static function ($app): D2_00002Rule {
            return new D2_00002Rule(
                $app->make(SiconfiClient::class),
            );
        });

        $this->app->singleton(D1_00018Rule::class, static fn (): D1_00018Rule => new D1_00018Rule());

        $this->app->singleton(D1_00028Rule::class, static fn (): D1_00028Rule => new D1_00028Rule());

        $this->app->singleton(D1_00037Rule::class, static fn (): D1_00037Rule => new D1_00037Rule());

        $this->app->singleton(D1_00034Rule::class, static fn (): D1_00034Rule => new D1_00034Rule());

        $this->app->singleton(D1_00035Rule::class, static fn (): D1_00035Rule => new D1_00035Rule());

        $this->app->singleton(D1_00036Rule::class, static fn (): D1_00036Rule => new D1_00036Rule());

        // $this->app->singleton(D1_ContinuidadeSaldoRule::class, static fn (): D1_ContinuidadeSaldoRule => new D1_ContinuidadeSaldoRule());

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
                $app->make(D1_00034Rule::class),
                $app->make(D1_00035Rule::class),
                $app->make(D1_00036Rule::class),
                new D1_00025Rule(),
                new D1_00026Rule(),
                new D1_00027Rule(),
                new D1_00029Rule(),
                new D1_00030Rule(),
                new D1_00031Rule(),
                new D1_00032Rule(),
                new D1_00033Rule(),
                new D1_00038Rule(),
                new D1_00039Rule(),
                new D1_00040Rule(),
                new D1_00041Rule(),
                new D1_00042Rule(),
                new D1_00043Rule(),
                new D1_00044Rule(),
                $app->make(D1_00028Rule::class),
                $app->make(D1_00037Rule::class),
                $app->make(D1_00001Rule::class),
                $app->make(D1_00002Rule::class),
                $app->make(D1_00003Rule::class),
                $app->make(D1_00006Rule::class),
                $app->make(D2_00001Rule::class),
                $app->make(D2_00002Rule::class),
                $app->make(D1_00018Rule::class),
                $app->make(D1_PatrimonialContinuidadeRule::class),
                $app->make(D1_OrcamentariaContinuidadeRule::class),
                $app->make(D1_ControleContinuidadeRule::class),
                // $app->make(D1_ContinuidadeSaldoRule::class), // Desativado: duplicava D1_00018 nas classes 7 e 8.
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
