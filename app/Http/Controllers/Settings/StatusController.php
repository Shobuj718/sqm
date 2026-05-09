<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StatusController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'availability_status' => ['required', Rule::in(['online', 'busy', 'away', 'offline'])],
        ]);

        $request->user()->update($validated);

        return back();
    }
}
