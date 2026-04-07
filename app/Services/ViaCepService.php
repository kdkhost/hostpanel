<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

class ViaCepService
{
    protected Client $http;

    public function __construct()
    {
        $this->http = new Client(['base_uri' => 'https://viacep.com.br/', 'timeout' => 10]);
    }

    public function lookup(string $cep): ?array
    {
        $cep = preg_replace('/\D/', '', $cep);

        if (strlen($cep) !== 8) {
            return null;
        }

        $cacheKey = "viacep.{$cep}";

        return Cache::remember($cacheKey, 3600, function () use ($cep) {
            try {
                $response = $this->http->get("ws/{$cep}/json/");
                $data     = json_decode($response->getBody()->getContents(), true);

                if (isset($data['erro'])) {
                    return null;
                }

                return [
                    'postcode'     => $cep,
                    'address'      => $data['logradouro'] ?? '',
                    'complement'   => $data['complemento'] ?? '',
                    'neighborhood' => $data['bairro'] ?? '',
                    'city'         => $data['localidade'] ?? '',
                    'state'        => $data['uf'] ?? '',
                    'ibge'         => $data['ibge'] ?? '',
                    'ddd'          => $data['ddd'] ?? '',
                ];
            } catch (\Exception) {
                return null;
            }
        });
    }
}
