<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGameClientRequest;
use App\Http\Requests\UpdateGameClientRequest;
use App\Models\GameClient;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GameClientController extends Controller
{
    public function index(): View
    {
        return view('admin.game-clients.index', [
            'clients' => GameClient::with('product')
                ->withCount(['stats as players_count', 'events as events_count'])
                ->orderBy('name')
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.game-clients.create', [
            'products' => $this->gameProducts(),
        ]);
    }

    public function store(StoreGameClientRequest $request): RedirectResponse
    {
        $secret = GameClient::generateSecret();

        $client = new GameClient($request->validated());
        $client->client_id = GameClient::generateClientId();
        $client->setPlainSecret($secret);
        $client->save();

        return redirect()
            ->route('admin.game-clients.edit', $client)
            ->with('status', 'Game client created. Copy the secret now: it is shown only once.')
            ->with('new_secret', $secret);
    }

    public function edit(GameClient $gameClient): View
    {
        return view('admin.game-clients.edit', [
            'client' => $gameClient->loadCount(['stats as players_count', 'events as events_count', 'tokens as tokens_count']),
            'products' => $this->gameProducts(),
        ]);
    }

    public function update(UpdateGameClientRequest $request, GameClient $gameClient): RedirectResponse
    {
        $gameClient->update($request->validated());

        return redirect()
            ->route('admin.game-clients.index')
            ->with('status', 'Game client updated.');
    }

    public function rotateSecret(GameClient $gameClient): RedirectResponse
    {
        $secret = GameClient::generateSecret();
        $gameClient->setPlainSecret($secret);
        $gameClient->save();

        return redirect()
            ->route('admin.game-clients.edit', $gameClient)
            ->with('status', 'Secret rotated. Update the game with the new value; the old secret no longer works.')
            ->with('new_secret', $secret);
    }

    public function destroy(GameClient $gameClient): RedirectResponse
    {
        $gameClient->delete();

        return redirect()
            ->route('admin.game-clients.index')
            ->with('status', 'Game client removed, along with its tokens, events, and stats.');
    }

    private function gameProducts()
    {
        return Product::where('category', config('games.category', 'Game'))->orderBy('name')->get(['id', 'name', 'product_code']);
    }
}
