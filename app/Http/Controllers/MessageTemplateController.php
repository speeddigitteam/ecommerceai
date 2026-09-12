<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageTemplateRequest;
use App\Models\MessageTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MessageTemplateController extends Controller
{
    public function index(string $channel): View
    {
        abort_unless(in_array($channel, ['email', 'sms'], true), 404);

        return view('communication.templates', ['channel' => $channel, 'templates' => MessageTemplate::query()->where('channel', $channel)->latest()->paginate(15)]);
    }

    public function store(StoreMessageTemplateRequest $request, string $channel): RedirectResponse
    {
        MessageTemplate::query()->create([...$request->safe()->only(['name', 'subject', 'body']), 'channel' => $channel, 'is_important' => $request->boolean('is_important')]);

        return back()->with('status', 'Message template created successfully.');
    }

    public function update(StoreMessageTemplateRequest $request, string $channel, MessageTemplate $messageTemplate): RedirectResponse
    {
        abort_unless($messageTemplate->channel === $channel, 404);
        $messageTemplate->update([...$request->safe()->only(['name', 'subject', 'body']), 'is_important' => $request->boolean('is_important')]);

        return back()->with('status', 'Message template updated successfully.');
    }

    public function destroy(string $channel, MessageTemplate $messageTemplate): RedirectResponse
    {
        abort_unless($messageTemplate->channel === $channel, 404);
        $messageTemplate->delete();

        return back()->with('status', 'Message template deleted successfully.');
    }
}
