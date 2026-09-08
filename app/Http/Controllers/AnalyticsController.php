<?php

namespace App\Http\Controllers;

use App\Exceptions\GoogleAnalyticsException;
use App\Models\WebsiteSetting;
use App\Services\GoogleAnalyticsReporter;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __construct(private readonly GoogleAnalyticsReporter $reporter) {}

    public function index(): View
    {
        $settings = WebsiteSetting::query()->first();
        $configured = $settings && $this->reporter->isConfigured($settings);
        $summary = null;
        $error = null;

        if ($configured) {
            try {
                $summary = $this->reporter->summary($settings);
            } catch (GoogleAnalyticsException $e) {
                $error = $e->getMessage();
            }
        }

        return view('analytics.index', [
            'configured' => $configured,
            'summary' => $summary,
            'error' => $error,
        ]);
    }
}
