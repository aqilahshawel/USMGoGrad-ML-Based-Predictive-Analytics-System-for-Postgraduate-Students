<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Some Laravel console output (Termwind) calls mb_strimwidth().
// On some Windows setups, ext-mbstring isn't enabled; Symfony's polyfills don't provide mb_strimwidth.
// This small polyfill is sufficient for console rendering and test runner output.
if (! function_exists('mb_strimwidth')) {
    function mb_strimwidth(
        string $string,
        int $start,
        int $width,
        string $trim_marker = '',
        ?string $encoding = null
    ): string {
        $slice = mb_substr($string, $start, null, $encoding);

        $markerWidth = $trim_marker !== '' ? mb_strwidth($trim_marker, $encoding) : 0;
        if ($width <= 0) {
            return '';
        }

        // If the slice already fits, return it as-is.
        if (mb_strwidth($slice, $encoding) <= $width) {
            return $slice;
        }

        $targetWidth = max(0, $width - $markerWidth);
        $len = mb_strlen($slice, $encoding);

        // Binary search for the longest prefix that fits into $targetWidth.
        $low = 0;
        $high = $len;

        while ($low < $high) {
            $mid = intdiv($low + $high + 1, 2);
            $candidate = mb_substr($slice, 0, $mid, $encoding);

            if (mb_strwidth($candidate, $encoding) <= $targetWidth) {
                $low = $mid;
            } else {
                $high = $mid - 1;
            }
        }

        return mb_substr($slice, 0, $low, $encoding) . $trim_marker;
    }
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
