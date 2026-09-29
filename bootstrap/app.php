<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Nederlandse boodschap per HTTP-status; nooit de Engelse standaardtekst van Laravel.
 */
$dutchMessage = function (int $status, Throwable $exception): string {
    $default = match ($status) {
        401 => 'Hier moet je voor inloggen.',
        403 => 'Dit mag je niet.',
        404 => 'Niet gevonden.',
        419 => 'Je sessie is verlopen. Laad de pagina opnieuw.',
        429 => 'Te veel bestellingen na elkaar. Probeer over een minuut opnieuw.',
        503 => 'Even in onderhoud. Probeer over een paar minuten opnieuw.',
        default => 'Er ging iets mis. Probeer opnieuw of bestel aan de toog.',
    };

    if (! $exception instanceof HttpExceptionInterface) {
        return $default;
    }

    // Een eigen boodschap (abort(404, 'Bestelling niet gevonden.')) mag door; framework-teksten
    // ("The route … could not be found.", model niet gevonden, throttle-middleware) niet.
    $message = $exception->getMessage();
    $isFrameworkText = $message === ''
        || $exception->getPrevious() instanceof ModelNotFoundException
        || str_starts_with($message, 'The route ')
        || str_starts_with($message, 'Too Many Attempts')
        || str_starts_with($message, 'This action is unauthorized')
        || str_starts_with($message, 'Service Unavailable')
        || str_starts_with($message, 'Server Error');

    return $isFrameworkText ? $default : $message;
};

$wantsJson = fn (Request $request): bool => $request->is('api/*') || $request->expectsJson();

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Achter de Plesk-nginx op dezelfde server: anders ziet Laravel het proxy-adres
        // (rate limits per IP, https-detectie, $request->ip()).
        $middleware->trustProxies(at: ['127.0.0.1', '::1']);

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) use ($dutchMessage, $wantsJson): void {
        $exceptions->shouldRenderJsonWhen($wantsJson);

        // 401: JSON krijgt een Nederlandse boodschap, de browser gaat naar de loginpagina met een toast.
        $exceptions->render(function (AuthenticationException $e, Request $request) use ($dutchMessage, $wantsJson) {
            if ($wantsJson($request)) {
                return response()->json(['message' => $dutchMessage(401, $e)], 401);
            }

            Inertia::flash('toast', ['type' => 'error', 'message' => $dutchMessage(401, $e)]);

            return redirect()->guest(route('login'));
        });

        // 403/404/419/429/500/503: JSON `{"message": …}` (bij 429 ook `retry_after`), web een Inertia-pagina `fout`.
        // Met APP_DEBUG blijft de debugpagina van Laravel voor 5xx staan (lokaal wil je de stacktrace zien).
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) use ($dutchMessage, $wantsJson): Response {
            $status = $response->getStatusCode();

            if (! in_array($status, [401, 403, 404, 419, 429, 500, 503], true)) {
                return $response;
            }

            if ($status >= 500 && config('app.debug') === true) {
                return $response;
            }

            $message = $dutchMessage($status, $e);
            $headers = $e instanceof HttpExceptionInterface ? $e->getHeaders() : [];

            if ($wantsJson($request)) {
                $body = ['message' => $message];

                if ($status === 429) {
                    $body['retry_after'] = (int) ($headers['Retry-After'] ?? 60);
                }

                return response()->json($body, $status, $headers);
            }

            if ($status === 419) {
                return back()->with('toast', ['type' => 'error', 'message' => $message]);
            }

            // Buiten de web-middleware (onbekende route) is er nog geen `appearance` gedeeld.
            View::share('appearance', 'dark');

            $page = Inertia::render('fout', ['status' => $status, 'message' => $message])
                ->toResponse($request)
                ->setStatusCode($status);

            foreach ($headers as $name => $value) {
                $page->headers->set($name, $value);
            }

            return $page;
        });
    })->create();
