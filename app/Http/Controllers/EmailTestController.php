<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class EmailTestController extends Controller
{
    public function create(Request $request): View
    {
        return view('email.test', [
            'defaultRecipient' => $request->user()->email ?? null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'to' => ['required', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        try {
            Mail::raw($data['message'], function ($mail) use ($data) {
                $mail->to($data['to'])
                    ->subject($data['subject']);
            });
        } catch (Throwable $e) {
            return back()
                ->withErrors(['email' => 'Unable to send test email: '.$e->getMessage()])
                ->withInput();
        }

        return back()->with('status', 'Test email sent to '.$data['to'].'.');
    }
}
