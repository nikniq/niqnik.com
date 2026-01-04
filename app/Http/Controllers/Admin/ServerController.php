<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServerRequest;
use App\Http\Requests\UpdateServerRequest;
use App\Models\Server;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ServerController extends Controller
{
    public function index(): View
    {
        return view('admin.servers.index', [
            'servers' => Server::orderBy('name')->paginate(20),
            'statuses' => Server::STATUSES,
        ]);
    }

    public function create(): View
    {
        return view('admin.servers.create', [
            'statuses' => Server::STATUSES,
        ]);
    }

    public function store(StoreServerRequest $request): RedirectResponse
    {
        Server::create($request->validated());

        return redirect()
            ->route('admin.servers.index')
            ->with('status', 'Server added.');
    }

    public function edit(Server $server): View
    {
        return view('admin.servers.edit', [
            'server' => $server,
            'statuses' => Server::STATUSES,
        ]);
    }

    public function update(UpdateServerRequest $request, Server $server): RedirectResponse
    {
        $server->update($request->validated());

        return redirect()
            ->route('admin.servers.index')
            ->with('status', 'Server updated.');
    }

    public function destroy(Server $server): RedirectResponse
    {
        $server->delete();

        return redirect()
            ->route('admin.servers.index')
            ->with('status', 'Server removed.');
    }
}
