<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_the_analytics_page(): void
    {
        $this->get(route('analytics.index'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_sees_setup_prompt_when_analytics_is_not_configured(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('analytics.index'))
            ->assertOk()
            ->assertSee('Connect Google Analytics');
    }

    public function test_admin_sees_a_friendly_error_when_google_rejects_the_credentials(): void
    {
        $admin = User::factory()->create();
        WebsiteSetting::factory()->create([
            'ga_property_id' => '123456789',
            'ga_service_account_json' => $this->fakeServiceAccountJson(),
        ]);
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['error' => 'invalid_grant', 'error_description' => 'Invalid JWT'], 400),
        ]);

        $this->actingAs($admin)
            ->get(route('analytics.index'))
            ->assertOk()
            ->assertSee('Could not load Analytics data');
    }

    public function test_admin_sees_charts_when_analytics_data_loads_successfully(): void
    {
        $admin = User::factory()->create();
        WebsiteSetting::factory()->create([
            'ga_property_id' => '123456789',
            'ga_service_account_json' => $this->fakeServiceAccountJson(),
        ]);
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'fake-token', 'expires_in' => 3600]),
            'analyticsdata.googleapis.com/*' => Http::response(['reports' => [
                ['rows' => [['metricValues' => [
                    ['value' => '120'], ['value' => '40'], ['value' => '150'], ['value' => '400'], ['value' => '95.5'], ['value' => '0.42'],
                ]]]],
                ['rows' => [
                    ['dimensionValues' => [['value' => '20260901']], 'metricValues' => [['value' => '50']]],
                    ['dimensionValues' => [['value' => '20260902']], 'metricValues' => [['value' => '70']]],
                ]],
                ['rows' => [
                    ['dimensionValues' => [['value' => '/']], 'metricValues' => [['value' => '300']]],
                    ['dimensionValues' => [['value' => '/shop']], 'metricValues' => [['value' => '100']]],
                ]],
            ]]),
        ]);

        $this->actingAs($admin)
            ->get(route('analytics.index'))
            ->assertOk()
            ->assertSee('Active users')
            ->assertSee('120')
            ->assertSee('/shop');
    }

    private function fakeServiceAccountJson(): string
    {
        // A throwaway RSA key used only to satisfy openssl_sign() in tests; never used for anything real.
        $privateKey = <<<'PEM'
        -----BEGIN PRIVATE KEY-----
        MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDIRmF48VnMxMGU
        qRcMSyZepZ73XgH1Zg8hlpVUizqhWtjGL3uH9KGgtxReOfQe/M3s4S8PlF3mBFp6
        xWL3lJn+l82qiHhGop78Qw1R8oJ+3IVgA0nEUAgBe10pws6khvRaGJ2ANSFA4Pqd
        L8PeifE4igKJ3MTRewjPlp06x17vy5c1xd/HztCZCmTjh22HxDZZK56of0EW49jb
        Gid8d8+6BVDEq/8pb8kgFifRC/b4AOq71wPibJFaxSDQ8aS9gqLykbF/U15puXfa
        r2oVHjB7617WetOFJ5zrixqXraGvxF/KWkCSKIOmbI0/62dwfdscC3n7Bp4v6Axr
        b13VsaYhAgMBAAECggEAFle+ZA8OOklGYglYfZHyCTkSOlKN3AyzahyHMv6ytHGS
        zCQGn1eWoya0P6wuJI7bglkU4Pi9CP46AdxSJYC54AHw0IeXs3miTE5GsXphPeSS
        1Jqeo3yyMp11T/ALEaZ5+DCAJwobrZtBGkF2k+BxEs0Cv31A+oXD/ooKUcEQQaa9
        rGa/XwTF26PSIxwJpPqESlC8TslLClg5yPoJ2olx4asQYKD5HnPZrSmx0RFfgLzQ
        5qmTqDw/xt1gAIk/xEha4bAvgFxuTh3dPo7K95fLLh5ppLDJ5AFoyk7i1M1bkH/k
        sEqQxcVqO716cST3PSsm9w+F9E7Jb/6+33znM7bbiQKBgQDn+dsH46BM3wjvsJF5
        FEU8J2qYEndM0kRiwvrywzk8YHfTLBoyb9PQavJ83plyq6A8KrXgSGR7bWrAgT98
        GI/gx6oRz+yhsL8LsTOpuWqxTEAuG6aLU1OzqlpJ1IYpd5EOFgBtQ+5h61CiXrZy
        YCUyPTGsNsZBwM7jZOydOM5JmwKBgQDdBBEWM4rB/ZgeqII95giD3TehvR7t9HFg
        z2pXLkNMrPhOMpWdCFVy0yIpseLkZYetTSN8SZKH9tYn5R2kPL3wkkyWEnhoXbO9
        W1GiZVycgf1RiNBdCQabapywa5DHfxyEs7M22cN1ADC7nLd0BTiy4Imn6RtlIwSQ
        nMah30XY8wKBgHUTkQvmQh0J48S05HarWFYHvrCJgVRwOrttlKG5DcX/GfqmI4KZ
        Fdn2X/PNlLxfCjvTgn9zieMRUfNeR+AltVfI7XiX6+GfBoysHlrmjYZ16V5b7i4b
        G/9tVqw0apG7GqZ1TDnZMxKQZN3N117aT2uPnuY/rse4u7HgWdrvtI1JAoGBAJAm
        Lt7EDyQnOigEz61/ct5OUhJzPtEENU5m+XyS9+HyjqCx7VLWNKUHf6iiFNHSRoOJ
        ZDOT6LY1D8hFbufHljppsPnQYifvtWMzRDMe2SBax9V93ihP0rda+Yc3IN580STb
        728+6HRPA3nZ5O0O2sQQHrgdGXkFNdz747F/5hZxAoGAOp+C7h3oyCE0tFBYh74m
        xUT3Zlagog7ZdYJDikfFnLDY5TA0scKAXIAtE+YTk1AbH8i+QgT+ywMaiC9vLBCy
        t6bFErSzZ+vYeCm/Ti9x8EmyT030TgGYQ6mfE5SurwEaxFmOiCaorl+HSH6A580Q
        1bItTE1s3aK8pFtnRuAHl4I=
        -----END PRIVATE KEY-----
        PEM;

        return json_encode([
            'client_email' => 'svc@example.iam.gserviceaccount.com',
            'private_key' => $privateKey,
        ]);
    }
}
