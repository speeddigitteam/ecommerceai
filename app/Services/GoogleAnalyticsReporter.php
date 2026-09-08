<?php

namespace App\Services;

use App\Exceptions\GoogleAnalyticsException;
use App\Models\WebsiteSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GoogleAnalyticsReporter
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const API_BASE = 'https://analyticsdata.googleapis.com/v1beta';

    private const SCOPE = 'https://www.googleapis.com/auth/analytics.readonly';

    public function isConfigured(WebsiteSetting $settings): bool
    {
        return filled($settings->ga_property_id) && filled($settings->ga_service_account_json);
    }

    /** @return array{totals: array<string, int|float>, trend: list<array{date: string, sessions: int}>, topPages: list<array{path: string, views: int}>} */
    public function summary(WebsiteSetting $settings, int $days = 28): array
    {
        $propertyId = $settings->ga_property_id;
        $accessToken = $this->accessToken($settings);
        $startDate = $days.'daysAgo';

        try {
            $response = Http::withToken($accessToken)
                ->post(self::API_BASE."/properties/{$propertyId}:batchRunReports", [
                    'requests' => [
                        [
                            'dateRanges' => [['startDate' => $startDate, 'endDate' => 'today']],
                            'metrics' => [
                                ['name' => 'activeUsers'],
                                ['name' => 'newUsers'],
                                ['name' => 'sessions'],
                                ['name' => 'screenPageViews'],
                                ['name' => 'averageSessionDuration'],
                                ['name' => 'bounceRate'],
                            ],
                        ],
                        [
                            'dateRanges' => [['startDate' => $startDate, 'endDate' => 'today']],
                            'dimensions' => [['name' => 'date']],
                            'metrics' => [['name' => 'sessions']],
                            'orderBys' => [['dimension' => ['dimensionName' => 'date']]],
                        ],
                        [
                            'dateRanges' => [['startDate' => $startDate, 'endDate' => 'today']],
                            'dimensions' => [['name' => 'pagePath']],
                            'metrics' => [['name' => 'screenPageViews']],
                            'orderBys' => [['metric' => ['metricName' => 'screenPageViews'], 'desc' => true]],
                            'limit' => 10,
                        ],
                    ],
                ]);
        } catch (ConnectionException $e) {
            throw new GoogleAnalyticsException('Could not reach Google Analytics: '.$e->getMessage());
        }

        if ($response->failed()) {
            throw new GoogleAnalyticsException($this->errorMessage($response));
        }

        $reports = $response->json('reports', []);

        return [
            'totals' => $this->parseTotals($reports[0] ?? []),
            'trend' => $this->parseTrend($reports[1] ?? []),
            'topPages' => $this->parseTopPages($reports[2] ?? []),
        ];
    }

    private function accessToken(WebsiteSetting $settings): string
    {
        $credentials = json_decode((string) $settings->ga_service_account_json, true);

        if (! is_array($credentials) || blank($credentials['client_email'] ?? null) || blank($credentials['private_key'] ?? null)) {
            throw new GoogleAnalyticsException('The Google service account JSON is missing a client_email or private_key.');
        }

        return Cache::remember(
            'google-analytics-token:'.md5($credentials['client_email'].$settings->ga_property_id),
            now()->addMinutes(55),
            fn (): string => $this->requestAccessToken($credentials)
        );
    }

    /** @param array{client_email: string, private_key: string} $credentials */
    private function requestAccessToken(array $credentials): string
    {
        $now = time();
        $jwt = $this->buildSignedJwt($credentials, $now);

        try {
            $response = Http::asForm()->post(self::TOKEN_URL, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);
        } catch (ConnectionException $e) {
            throw new GoogleAnalyticsException('Could not reach Google: '.$e->getMessage());
        }

        if ($response->failed() || blank($response->json('access_token'))) {
            throw new GoogleAnalyticsException($this->errorMessage($response, 'Could not authenticate with the Google service account.'));
        }

        return $response->json('access_token');
    }

    /** @param array{client_email: string, private_key: string} $credentials */
    private function buildSignedJwt(array $credentials, int $now): string
    {
        $segments = [
            $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])),
            $this->base64UrlEncode(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => self::SCOPE,
                'aud' => self::TOKEN_URL,
                'iat' => $now,
                'exp' => $now + 3600,
            ])),
        ];

        $signature = '';
        $signed = openssl_sign(implode('.', $segments), $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);

        if (! $signed) {
            throw new GoogleAnalyticsException('Could not sign the Google service account request. Check that the private key is valid.');
        }

        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    /** @param array<string, mixed> $report */
    private function parseTotals(array $report): array
    {
        $values = $report['rows'][0]['metricValues'] ?? [];
        $names = ['activeUsers', 'newUsers', 'sessions', 'screenPageViews', 'averageSessionDuration', 'bounceRate'];
        $totals = [];

        foreach ($names as $index => $name) {
            $totals[$name] = (float) ($values[$index]['value'] ?? 0);
        }

        return $totals;
    }

    /** @param array<string, mixed> $report */
    private function parseTrend(array $report): array
    {
        return collect($report['rows'] ?? [])
            ->map(fn (array $row): array => [
                'date' => Carbon::createFromFormat('Ymd', $row['dimensionValues'][0]['value'])->format('M d'),
                'sessions' => (int) ($row['metricValues'][0]['value'] ?? 0),
            ])
            ->all();
    }

    /** @param array<string, mixed> $report */
    private function parseTopPages(array $report): array
    {
        return collect($report['rows'] ?? [])
            ->map(fn (array $row): array => [
                'path' => $row['dimensionValues'][0]['value'] ?? '/',
                'views' => (int) ($row['metricValues'][0]['value'] ?? 0),
            ])
            ->all();
    }

    private function errorMessage(Response $response, string $fallback = 'The Google Analytics request failed.'): string
    {
        return $response->json('error.message') ?? $fallback;
    }
}
