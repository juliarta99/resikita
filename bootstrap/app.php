<?php

declare(strict_types=1);

use App\Exceptions\AturanBisnisException;
use App\Http\Middleware\PastikanTokoTerverifikasi;
use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Batas laju umum kanal API. Definisinya di AppServiceProvider.
        $middleware->api(prepend: ['throttle:api']);

        /*
        | Halaman masuk Resikita bernama `masuk`, bukan `login`. Tanpa
        | pemetaan ini, middleware auth mencari route bernama `login`
        | dan tamu yang membuka halaman panel menerima galat 500 alih-alih
        | diantar ke formulir masuk.
        */
        $middleware->redirectGuestsTo(fn () => route('masuk'));

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,

            /*
            | Panel penjual dijaga status tokonya, bukan matinya akun
            | pemilik. Lihat PastikanTokoTerverifikasi untuk alasannya.
            */
            'toko.terverifikasi' => PastikanTokoTerverifikasi::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
        | Setiap galat pada kanal API keluar dalam amplop yang sama
        | (CLAUDE.md 12). Kalau tidak dipusatkan di sini, sebagian galat
        | akan lolos dalam format bawaan Laravel dan aplikasi mobile
        | menemui dua bentuk response gagal yang berbeda, tepat pada
        | saat yang paling buruk, yaitu ketika ada yang salah.
        */
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return match (true) {
                $e instanceof ValidationException => ApiResponse::validasiGagal($e->errors(), $e->getMessage()),

                $e instanceof AuthenticationException => ApiResponse::belumLogin(),

                $e instanceof AturanBisnisException => ApiResponse::gagal($e->getMessage(), $e->statusHttp),

                $e instanceof UnauthorizedException,
                $e instanceof AuthorizationException => ApiResponse::tidakBerwenang(
                    $e->getMessage() !== '' ? $e->getMessage() : 'Anda tidak berwenang melakukan tindakan ini.',
                ),

                $e instanceof ModelNotFoundException,
                $e instanceof NotFoundHttpException => ApiResponse::tidakDitemukan(),

                $e instanceof TooManyRequestsHttpException => ApiResponse::gagal(
                    'Terlalu banyak permintaan. Coba lagi beberapa saat lagi.',
                    429,
                ),

                // Galat tak terduga tidak pernah membocorkan pesan
                // internal ke klien di produksi. Detailnya tetap masuk
                // log untuk ditelusuri.
                default => $e instanceof HttpExceptionInterface
                    ? ApiResponse::gagal($e->getMessage() ?: 'Permintaan tidak dapat diproses.', $e->getStatusCode())
                    : ApiResponse::gagal(
                        config('app.debug') ? $e->getMessage() : 'Terjadi kesalahan pada peladen.',
                        500,
                    ),
            };
        });
    })->create();
