<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExternalLog;
use Illuminate\View\View;

class ExternalLogController extends Controller
{
    public function index(): View
    {
        $logs = ExternalLog::latest()->paginate(50);

        return view('admin.logs.external', [
            'logs' => $logs,
        ]);
    }
}
