<?php

namespace App\Http\Controllers;

use App\Actions\WebSockets\GenerateWebSocketToken;
use App\Actions\WebSockets\ResolveWebSocketUrl;
use App\Http\Resources\ServerResource;
use App\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('servers/{server}/console')]
#[Middleware(['auth', 'has-project'])]
class ConsoleController extends Controller
{
    #[Get('/', name: 'console')]
    public function index(Server $server): Response
    {
        $this->authorize('update', $server);

        return Inertia::render('servers/console', [
            'server' => ServerResource::make($server),
        ]);
    }

    #[Post('/token', name: 'console.token')]
    public function token(Server $server, Request $request): JsonResponse
    {
        $this->authorize('update', $server);

        $this->validate($request, [
            'user' => [
                'required',
                Rule::in($server->getSshUsers()),
            ],
        ]);

        $result = app(GenerateWebSocketToken::class)->generate('terminal_token', [
            'server_id' => $server->id,
            'user_id' => $request->user()->id,
            'ssh_user' => $request->input('user'),
        ]);
        $result['url'] = app(ResolveWebSocketUrl::class)->forRequest($request, '/ws/terminal');

        return response()->json($result);
    }
}
