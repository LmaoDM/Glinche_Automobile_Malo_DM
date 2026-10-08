<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GlincheApiClient
{
    public function vehicles(): array
    {
        $token = $this->authenticate();

        try {
            $response = $this->request()
                ->withToken($token)
                ->get('/partners/vehicles');

            if ($response->failed()) {
                throw new RuntimeException("L'API Glinche a refusé la récupération des véhicules ({$response->status()}).");
            }

            $payload = $response->json();
            $vehicles = is_array($payload) && array_is_list($payload)
                ? $payload
                : data_get($payload, 'data');

            if (! is_array($vehicles) || ! array_is_list($vehicles)) {
                throw new RuntimeException("La réponse de l'API Glinche ne contient pas une liste de véhicules valide.");
            }

            return $vehicles;
        } finally {
            $this->logout($token);
        }
    }

    private function authenticate(): string
    {
        $email = config('services.glinche.email');
        $password = config('services.glinche.password');

        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            throw new RuntimeException('Les identifiants de l’API Glinche ne sont pas configurés.');
        }

        $response = $this->request()->post('/partners/login', [
            'email' => $email,
            'password' => $password,
        ]);

        $token = $response->json('token');

        if ($response->failed() || ! is_string($token) || $token === '') {
            throw new RuntimeException("L'authentification auprès de l'API Glinche a échoué ({$response->status()}).");
        }

        return $token;
    }

    private function logout(string $token): void
    {
        $this->request()->withToken($token)->post('/partners/logout');
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl((string) config('services.glinche.base_url'))
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->retry(2, 500, throw: false);
    }
}
