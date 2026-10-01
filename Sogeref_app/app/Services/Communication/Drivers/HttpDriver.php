<?php

namespace App\Services\Communication\Drivers;

use App\Services\Communication\SendResult;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Base des pilotes HTTP : délais courts, erreurs converties en SendResult. */
abstract class HttpDriver implements ChannelDriver
{
    public function __construct(protected array $config) {}

    abstract protected function provider(): string;

    abstract protected function dispatch(array $message): SendResult;

    public function send(array $message): SendResult
    {
        try {
            return $this->dispatch($message);
        } catch (Throwable $e) {
            Log::warning('Communication: échec '.$this->provider(), ['error' => $e->getMessage()]);

            return SendResult::failed($this->provider(), 'Erreur de connexion : '.$e->getMessage());
        }
    }

    protected function http(): PendingRequest
    {
        return Http::connectTimeout(5)->timeout(15)->acceptJson();
    }

    protected function failure(Response $response): SendResult
    {
        $json = $response->json();
        $detail = data_get($json, 'error.message') ?? data_get($json, 'message') ?? data_get($json, 'requestError.serviceException.text')
            ?? data_get($json, 'SMSMessageData.Message') ?? mb_substr($response->body(), 0, 300);

        return SendResult::failed($this->provider(), 'HTTP '.$response->status().' — '.$detail);
    }

    /** Numéro sans « + » (format attendu par Meta). */
    protected function digits(string $phone): string
    {
        return preg_replace('/\D/', '', $phone);
    }
}
