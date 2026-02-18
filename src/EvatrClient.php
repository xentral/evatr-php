<?php

declare(strict_types=1);

namespace Xentral\EvatrPhp;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Xentral\EvatrPhp\Enum\StatusCode;
use Xentral\EvatrPhp\Exception\EvatrException;
use Xentral\EvatrPhp\Exception\ForbiddenException;
use Xentral\EvatrPhp\Exception\NotFoundException;
use Xentral\EvatrPhp\Exception\ServiceException;
use Xentral\EvatrPhp\Exception\ValidationException;
use Xentral\EvatrPhp\Request\ConfirmationQuery;
use Xentral\EvatrPhp\Response\ConfirmationResult;
use Xentral\EvatrPhp\Response\MemberState;
use Xentral\EvatrPhp\Response\StatusMessage;

class EvatrClient
{
    private ClientInterface $httpClient;

    public function __construct(
        ?ClientInterface $httpClient = null,
        private readonly string $baseUrl = 'https://api.evatr.vies.bzst.de/app',
    ) {
        $this->httpClient = $httpClient ?? new Client();
    }

    /**
     * @throws EvatrException
     */
    public function verifyVatId(ConfirmationQuery $query): ConfirmationResult
    {
        $response = $this->request('POST', '/v1/abfrage', [
            'json' => $query->toArray(),
        ]);

        return ConfirmationResult::fromArray($response);
    }

    /**
     * @return StatusMessage[]
     * @throws EvatrException
     */
    public function getStatusMessages(): array
    {
        $response = $this->request('GET', '/v1/info/statusmeldungen');

        return array_map(
            static fn(array $item) => StatusMessage::fromArray($item),
            $response,
        );
    }

    /**
     * @return MemberState[]
     * @throws EvatrException
     */
    public function getMemberStates(): array
    {
        $response = $this->request('GET', '/v1/info/eu_mitgliedstaaten');

        return array_map(
            static fn(array $item) => MemberState::fromArray($item),
            $response,
        );
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     * @throws EvatrException
     */
    private function request(string $method, string $path, array $options = []): array
    {
        try {
            $response = $this->httpClient->request($method, $this->baseUrl . $path, $options);
        } catch (GuzzleException $e) {
            $this->handleGuzzleException($e);
        }

        $body = (string) $response->getBody();
        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        return $data;
    }

    /**
     * @throws EvatrException
     * @return never
     */
    private function handleGuzzleException(GuzzleException $e): never
    {
        $statusCode = null;
        $httpCode = 0;
        $message = $e->getMessage();

        if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
            $response = $e->getResponse();
            $httpCode = $response->getStatusCode();
            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            if (is_array($data) && isset($data['status'])) {
                $statusCode = StatusCode::tryFrom($data['status']);
                $message = $data['meldung'] ?? $message;
            }

            throw match (true) {
                $httpCode === 400 => new ValidationException($message, $statusCode, $httpCode, $e),
                $httpCode === 403 => new ForbiddenException($message, $statusCode, $httpCode, $e),
                $httpCode === 404 => new NotFoundException($message, $statusCode, $httpCode, $e),
                $httpCode >= 500 => new ServiceException($message, $statusCode, $httpCode, $e),
                default => new EvatrException($message, $statusCode, $httpCode, $e),
            };
        }

        throw new EvatrException($message, null, 0, $e);
    }
}
