<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourierIntegrationRequest;
use App\Models\CourierIntegration;
use App\Services\SteadfastCourier;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class CourierIntegrationController extends Controller
{
    public function index(): View
    {
        return view('courier-integrations.index', [
            'integrations' => CourierIntegration::query()->latest()->paginate(15),
        ]);
    }

    public function store(StoreCourierIntegrationRequest $request): RedirectResponse
    {
        CourierIntegration::query()->create($request->validated());

        return to_route('courier-integrations.index')->with('status', 'Courier integration added successfully.');
    }

    public function testConnection(CourierIntegration $courierIntegration, SteadfastCourier $steadfast): RedirectResponse
    {
        abort_unless($courierIntegration->provider === 'steadfast', 422);

        try {
            $balance = $steadfast->balance($courierIntegration);
            $courierIntegration->update([
                'last_tested_at' => now(),
                'last_test_succeeded' => true,
                'last_error' => null,
            ]);

            return to_route('courier-integrations.index')->with('status', 'Steadfast connected successfully. Current balance: BDT '.number_format($balance, 2));
        } catch (Throwable $exception) {
            report($exception);
            $courierIntegration->update([
                'last_tested_at' => now(),
                'last_test_succeeded' => false,
                'last_error' => str($exception->getMessage())->limit(1000)->toString(),
            ]);

            return to_route('courier-integrations.index')->with('error', 'Steadfast connection failed. Check the credentials and try again.');
        }
    }

    public function toggle(CourierIntegration $courierIntegration): RedirectResponse
    {
        $courierIntegration->update(['is_active' => ! $courierIntegration->is_active]);

        return to_route('courier-integrations.index')->with('status', 'Courier status updated successfully.');
    }

    public function destroy(CourierIntegration $courierIntegration): RedirectResponse
    {
        $courierIntegration->delete();

        return to_route('courier-integrations.index')->with('status', 'Courier integration removed successfully.');
    }
}
