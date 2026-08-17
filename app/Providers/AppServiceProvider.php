<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiting();
    }

    /**
     * Batas laju umum kanal API.
     *
     * Endpoint yang mahal, autentikasi, pengajuan wilayah, dan dua
     * kanal AI, punya batasnya sendiri di routes/api.php. Batas ini
     * adalah jaring pengaman untuk sisanya: tanpa satu pun batas umum,
     * satu klien yang salah tulis perulangan bisa menghabiskan peladen
     * tanpa niat jahat sama sekali.
     *
     * Kunci penghitungnya id pengguna bila ada token, dan alamat IP
     * bila tidak. Memakai IP saja akan menghukum seluruh pengguna di
     * balik satu NAT kantor atau jaringan seluler yang sama.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', fn (Request $request): Limit => Limit::perMinute(60)
            ->by($request->user()?->id ?: $request->ip()));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
