<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNoticeRequest;
use App\Models\Notice;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NoticeController extends Controller
{
    public function index(): View
    {
        return view('communication.notices', ['notices' => Notice::query()->latest()->paginate(15)]);
    }

    public function store(StoreNoticeRequest $request): RedirectResponse
    {
        Notice::query()->create([...$request->safe()->only(['name', 'details', 'publish_to']), 'is_active' => $request->boolean('is_active')]);

        return back()->with('status', 'Notice created successfully.');
    }

    public function update(StoreNoticeRequest $request, Notice $notice): RedirectResponse
    {
        $notice->update([...$request->safe()->only(['name', 'details', 'publish_to']), 'is_active' => $request->boolean('is_active')]);

        return back()->with('status', 'Notice updated successfully.');
    }

    public function destroy(Notice $notice): RedirectResponse
    {
        $notice->delete();

        return back()->with('status', 'Notice deleted successfully.');
    }
}
