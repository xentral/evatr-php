<?php

declare(strict_types=1);

namespace Xentral\EvatrPhp\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Xentral\EvatrPhp\Enum\ComparisonResult;
use Xentral\EvatrPhp\Enum\StatusCode;
use Xentral\EvatrPhp\EvatrClient;
use Xentral\EvatrPhp\Exception\ForbiddenException;
use Xentral\EvatrPhp\Exception\NotFoundException;
use Xentral\EvatrPhp\Exception\ServiceException;
use Xentral\EvatrPhp\Exception\ValidationException;
use Xentral\EvatrPhp\Request\ConfirmationQuery;
use Xentral\EvatrPhp\Response\ConfirmationResult;
use Xentral\EvatrPhp\Response\MemberState;
use Xentral\EvatrPhp\Response\StatusMessage;

final class EvatrClientTest extends TestCase
{
    private function createClientWithMock(MockHandler $mock): EvatrClient
    {
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        return new EvatrClient($httpClient);
    }

    public function testSimpleQueryReturnsValidResult(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'status' => 'evatr-0000',
                'anfrageZeitpunkt' => '2026-02-17T10:00:00Z',
                'id' => 'abc-123',
            ])),
        ]);

        $client = $this->createClientWithMock($mock);
        $query = ConfirmationQuery::simple('DE123456789', 'ATU12345678');
        $result = $client->verifyVatId($query);

        $this->assertInstanceOf(ConfirmationResult::class, $result);
        $this->assertSame(StatusCode::VALID, $result->status);
        $this->assertSame('2026-02-17T10:00:00Z', $result->queryTimestamp);
        $this->assertSame('abc-123', $result->id);
        $this->assertTrue($result->isValid());
    }

    public function testQualifiedQueryReturnsComparisonResults(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'status' => 'evatr-0000',
                'anfrageZeitpunkt' => '2026-02-17T10:00:00Z',
                'ergFirmenname' => 'Test GmbH',
                'ergOrt' => 'Berlin',
                'ergStrasse' => 'Teststr. 1',
                'ergPlz' => '10115',
                'ergFirmennameResult' => 'A',
                'ergOrtResult' => 'A',
                'ergStrasseResult' => 'B',
                'ergPlzResult' => 'C',
            ])),
        ]);

        $client = $this->createClientWithMock($mock);
        $query = ConfirmationQuery::qualified(
            'DE123456789',
            'ATU12345678',
            'Test GmbH',
            'Berlin',
            'Teststr. 1',
            '10115',
        );
        $result = $client->verifyVatId($query);

        $this->assertSame(StatusCode::VALID, $result->status);
        $this->assertSame('Test GmbH', $result->companyName);
        $this->assertSame(ComparisonResult::MATCH, $result->companyNameResult);
        $this->assertSame(ComparisonResult::MATCH, $result->cityResult);
        $this->assertSame(ComparisonResult::MISMATCH, $result->streetResult);
        $this->assertSame(ComparisonResult::NOT_REQUESTED, $result->postalCodeResult);
    }

    public function testInvalidVatIdReturnsNonValidResult(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'status' => 'evatr-2006',
                'anfrageZeitpunkt' => '2026-02-17T10:00:00Z',
                'gueltigAb' => '2020-01-01',
                'gueltigBis' => '2024-12-31',
            ])),
        ]);

        $client = $this->createClientWithMock($mock);
        $query = ConfirmationQuery::simple('DE123456789', 'ATU12345678');
        $result = $client->verifyVatId($query);

        $this->assertSame(StatusCode::VALID_PAST, $result->status);
        $this->assertFalse($result->isValid());
        $this->assertSame('2020-01-01', $result->validFrom);
        $this->assertSame('2024-12-31', $result->validUntil);
    }

    public function testValidationExceptionOn400(): void
    {
        $mock = new MockHandler([
            new Response(400, ['Content-Type' => 'application/json'], json_encode([
                'status' => 'evatr-0004',
                'meldung' => 'Die anfragende USt-IdNr. ist syntaktisch inkorrekt.',
            ])),
        ]);

        $client = $this->createClientWithMock($mock);
        $query = ConfirmationQuery::simple('INVALID', 'ATU12345678');

        $this->expectException(ValidationException::class);
        $client->verifyVatId($query);
    }

    public function testForbiddenExceptionOn403(): void
    {
        $mock = new MockHandler([
            new Response(403, ['Content-Type' => 'application/json'], json_encode([
                'status' => 'evatr-0006',
                'meldung' => 'Nicht berechtigt.',
            ])),
        ]);

        $client = $this->createClientWithMock($mock);
        $query = ConfirmationQuery::simple('DE123456789', 'DE987654321');

        $this->expectException(ForbiddenException::class);
        $client->verifyVatId($query);
    }

    public function testNotFoundExceptionOn404(): void
    {
        $mock = new MockHandler([
            new Response(404, ['Content-Type' => 'application/json'], json_encode([
                'status' => 'evatr-2001',
                'meldung' => 'Die angefragte USt-IdNr. ist nicht vergeben.',
            ])),
        ]);

        $client = $this->createClientWithMock($mock);
        $query = ConfirmationQuery::simple('DE123456789', 'ATU00000000');

        $this->expectException(NotFoundException::class);
        $client->verifyVatId($query);
    }

    public function testServiceExceptionOn500(): void
    {
        $mock = new MockHandler([
            new Response(500, ['Content-Type' => 'application/json'], json_encode([
                'status' => 'evatr-2004',
                'meldung' => 'Verarbeitung nicht möglich.',
            ])),
        ]);

        $client = $this->createClientWithMock($mock);
        $query = ConfirmationQuery::simple('DE123456789', 'ATU12345678');

        $this->expectException(ServiceException::class);
        $client->verifyVatId($query);
    }

    public function testServiceExceptionOn503(): void
    {
        $mock = new MockHandler([
            new Response(503, ['Content-Type' => 'application/json'], json_encode([
                'status' => 'evatr-1001',
                'meldung' => 'Service temporär nicht verfügbar.',
            ])),
        ]);

        $client = $this->createClientWithMock($mock);
        $query = ConfirmationQuery::simple('DE123456789', 'ATU12345678');

        $this->expectException(ServiceException::class);
        $client->verifyVatId($query);
    }

    public function testGetStatusMessages(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                [
                    'status' => 'evatr-0000',
                    'kategorie' => 'success',
                    'httpcode' => 200,
                    'meldung' => 'Die angefragte USt-IdNr. ist gültig.',
                ],
                [
                    'status' => 'evatr-0002',
                    'kategorie' => 'error',
                    'httpcode' => 400,
                    'feld' => 'anfragendeUstid',
                    'meldung' => 'Pflichtfeld fehlt.',
                ],
            ])),
        ]);

        $client = $this->createClientWithMock($mock);
        $messages = $client->getStatusMessages();

        $this->assertCount(2, $messages);
        $this->assertInstanceOf(StatusMessage::class, $messages[0]);
        $this->assertSame('evatr-0000', $messages[0]->status);
        $this->assertSame(200, $messages[0]->httpCode);
        $this->assertNull($messages[0]->field);
        $this->assertSame('anfragendeUstid', $messages[1]->field);
    }

    public function testGetMemberStates(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                [
                    'alpha2' => 'AT',
                    'name' => 'Österreich',
                    'verfuegbar' => true,
                ],
                [
                    'alpha2' => 'FR',
                    'name' => 'Frankreich',
                    'verfuegbar' => false,
                ],
            ])),
        ]);

        $client = $this->createClientWithMock($mock);
        $states = $client->getMemberStates();

        $this->assertCount(2, $states);
        $this->assertInstanceOf(MemberState::class, $states[0]);
        $this->assertSame('AT', $states[0]->countryCode);
        $this->assertSame('Österreich', $states[0]->name);
        $this->assertTrue($states[0]->available);
        $this->assertFalse($states[1]->available);
    }

    public function testConfirmationQueryToArraySimple(): void
    {
        $query = ConfirmationQuery::simple('DE123456789', 'ATU12345678');
        $array = $query->toArray();

        $this->assertSame([
            'anfragendeUstid' => 'DE123456789',
            'angefragteUstid' => 'ATU12345678',
        ], $array);
    }

    public function testConfirmationQueryToArrayQualified(): void
    {
        $query = ConfirmationQuery::qualified(
            'DE123456789',
            'ATU12345678',
            'Test GmbH',
            'Berlin',
            'Teststr. 1',
            '10115',
        );
        $array = $query->toArray();

        $this->assertSame([
            'anfragendeUstid' => 'DE123456789',
            'angefragteUstid' => 'ATU12345678',
            'firmenname' => 'Test GmbH',
            'ort' => 'Berlin',
            'strasse' => 'Teststr. 1',
            'plz' => '10115',
        ], $array);
    }

    public function testExceptionCarriesStatusCode(): void
    {
        $mock = new MockHandler([
            new Response(400, ['Content-Type' => 'application/json'], json_encode([
                'status' => 'evatr-0005',
                'meldung' => 'Die angefragte USt-IdNr. ist syntaktisch inkorrekt.',
            ])),
        ]);

        $client = $this->createClientWithMock($mock);

        try {
            $client->verifyVatId(ConfirmationQuery::simple('DE123456789', 'INVALID'));
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame(StatusCode::FOREIGN_VAT_ID_INVALID, $e->statusCode);
            $this->assertSame(400, $e->getCode());
        }
    }
}
