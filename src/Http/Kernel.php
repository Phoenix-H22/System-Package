<?php

namespace MainSys\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The middleware stack for your package.
     * These middleware are run during every request made to your package's endpoints.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        \Illuminate\Http\Middleware\HandleCors::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * The middleware groups specific to your package.
     * You can add custom groups for routes defined in the package.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
    ];

    /**
     * The middleware aliases for your package.
     * Use these aliases to assign middleware to specific routes within the package.
     *
     * @var array<string, class-string|string>
     */
    protected $middlewareAliases = [
        'auth.token' => \MainSys\Http\Middleware\AuthenticateToken::class,
    ];
}
