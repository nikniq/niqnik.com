<?php

namespace App\Http\Controllers;

use App\Models\License;
use Illuminate\View\View;

class PublicLicenseValidatorController extends Controller
{
    public function __invoke(string $license_code): View
    {
        $license = License::with('product')->where('identifier', $license_code)->firstOrFail();

        return view('licenses.validator', [
            'license' => $license,
        ]);
    }
}
