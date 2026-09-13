<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageTemplateRequest;
use App\Models\MessageTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageTemplateController extends Controller
{
    public function index(Request $request, string $channel): View
    {
        abort_unless(in_array($channel, ['email', 'sms'], true), 404);
        $category = $request->string('category')->toString();

        return view('communication.templates', [
            'channel' => $channel,
            'category' => $category,
            'templates' => MessageTemplate::query()->where('channel', $channel)
                ->when($category, fn ($query) => $query->where('category', $category))
                ->orderBy('category')->orderBy('name')->paginate(15)->withQueryString(),
        ]);
    }

    public function store(StoreMessageTemplateRequest $request, string $channel): RedirectResponse
    {
        MessageTemplate::query()->create([...$request->safe()->only(['name', 'subject', 'body', 'category', 'preview_text', 'button_text', 'button_url']), 'channel' => $channel, 'is_important' => $request->boolean('is_important'), 'is_active' => $request->boolean('is_active')]);

        return back()->with('status', 'Message template created successfully.');
    }

    public function update(StoreMessageTemplateRequest $request, string $channel, MessageTemplate $messageTemplate): RedirectResponse
    {
        abort_unless($messageTemplate->channel === $channel, 404);
        $messageTemplate->update([...$request->safe()->only(['name', 'subject', 'body', 'category', 'preview_text', 'button_text', 'button_url']), 'is_important' => $request->boolean('is_important'), 'is_active' => $request->boolean('is_active')]);

        return back()->with('status', 'Message template updated successfully.');
    }

    public function toggle(string $channel, MessageTemplate $messageTemplate): RedirectResponse
    {
        abort_unless($messageTemplate->channel === $channel, 404);
        $messageTemplate->update(['is_active' => ! $messageTemplate->is_active]);

        return back()->with('status', $messageTemplate->name.' '.($messageTemplate->is_active ? 'enabled' : 'disabled').'.');
    }

    public function destroy(string $channel, MessageTemplate $messageTemplate): RedirectResponse
    {
        abort_unless($messageTemplate->channel === $channel, 404);
        $messageTemplate->delete();

        return back()->with('status', 'Message template deleted successfully.');
    }
}
