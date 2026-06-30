<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class SsoTokenService
{
    public function verify(string $token): array
    {
        $baseUrl = rtrim((string) config('services.sso.base_url'), '/');

        if ($baseUrl === '') {
            throw new RuntimeException('Konfigurasi SSO_BASE_URL belum diatur.');
        }

        $response = Http::asForm()
            ->acceptJson()
            ->post($baseUrl . '/api/sso/verify-token', [
                'client_id' => config('services.sso.client_id'),
                'client_secret' => config('services.sso.client_secret'),
                'sso_token' => $token,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                $response->json('message', 'Token SSO tidak valid.')
            );
        }

        $user = $response->json('user');

        if (! is_array($user)) {
            throw new RuntimeException('Response user dari SSO tidak valid.');
        }

        return $user;
    }
}
