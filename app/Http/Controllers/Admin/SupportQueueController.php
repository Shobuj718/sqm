<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FacebookPage;
use App\Models\SupportQueue;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class SupportQueueController extends Controller
{
    public function index()
    {
        $queues = SupportQueue::with(['facebookPages', 'users'])->get();
        return view('admin.support-queues.index', compact('queues'));
    }

    public function create()
    {
        return view('admin.support-queues.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        SupportQueue::create($validated);

        return redirect()->route('support-queues.index')->with('success', 'Support queue created successfully.');
    }

    public function edit(SupportQueue $supportQueue)
    {
        return view('admin.support-queues.edit', compact('supportQueue'));
    }

    public function assignPages(Request $request, SupportQueue $supportQueue): RedirectResponse
    {
        $validated = $request->validate([
            'page_ids' => 'nullable|array',
            'page_ids.*' => 'exists:facebook_pages,id',
        ]);

        $supportQueue->facebookPages()->sync($validated['page_ids'] ?? []);

        return back()->with('success', 'Support queue pages updated successfully.');
    }

    public function show(SupportQueue $supportQueue)
    {
        $pages = FacebookPage::all();
        $agents = User::whereHas('roles', function ($q) {
            $q->where('name', 'agent');
        })->get();

        return view('admin.support-queues.show', compact('supportQueue', 'pages', 'agents'));
    }

    public function update(Request $request, SupportQueue $supportQueue): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $supportQueue->update($validated);

        return redirect()->route('support-queues.show', $supportQueue)->with('success', 'Support queue updated successfully.');
    }

    public function assignAgents(Request $request, SupportQueue $supportQueue): RedirectResponse
    {
        $validated = $request->validate([
            'agent_ids' => 'nullable|array',
            'agent_ids.*' => 'exists:users,id',
        ]);

        $supportQueue->users()->sync($validated['agent_ids'] ?? []);

        return back()->with('success', 'Support queue agents updated successfully.');
    }

    public function assignBoth(Request $request, SupportQueue $supportQueue): RedirectResponse
    {
        $validated = $request->validate([
            'page_ids' => 'nullable|array',
            'page_ids.*' => 'exists:facebook_pages,id',
            'agent_ids' => 'nullable|array',
            'agent_ids.*' => 'exists:users,id',
        ]);

        $supportQueue->facebookPages()->sync($validated['page_ids'] ?? []);
        $supportQueue->users()->sync($validated['agent_ids'] ?? []);

        return back()->with('success', 'Support queue pages and agents updated successfully.');
    }
}
