<?php declare(strict_types=1);

namespace Patchlevel\EventSourcing\Tests\Unit\Aggregate;

use DateTimeImmutable;
use Patchlevel\EventSourcing\Aggregate\AggregateException;
use Patchlevel\EventSourcing\Tests\Unit\Fixture\Email;
use Patchlevel\EventSourcing\Tests\Unit\Fixture\ProfileCreated;
use Patchlevel\EventSourcing\Tests\Unit\Fixture\ProfileId;
use PHPUnit\Framework\TestCase;

class AggregateChangedTest extends TestCase
{
    public function testCreateEvent()
    {
        $id = ProfileId::fromString('1');
        $email = Email::fromString('d.a.badura@gmail.com');

        $event = ProfileCreated::raise($id, $email);

        self::assertEquals($id, $event->profileId());
        self::assertEquals($email, $event->email());
        self::assertEquals($id->toString(), $event->aggregateId());
        self::assertEquals(null, $event->playhead());
        self::assertEquals(null, $event->recordedOn());
        self::assertEquals(
            [
                'profileId' => '1',
                'email' => 'd.a.badura@gmail.com',
            ],
            $event->payload()
        );
    }

    public function testRecordNow()
    {
        $id = ProfileId::fromString('1');
        $email = Email::fromString('d.a.badura@gmail.com');

        $event = ProfileCreated::raise($id, $email);
        $recordedEvent = $event->recordNow(0);

        self::assertEquals($id, $recordedEvent->profileId());
        self::assertEquals($email, $recordedEvent->email());
        self::assertEquals(0, $recordedEvent->playhead());
        self::assertInstanceOf(DateTimeImmutable::class, $recordedEvent->recordedOn());
        self::assertEquals(
            [
                'profileId' => '1',
                'email' => 'd.a.badura@gmail.com',
            ],
            $recordedEvent->payload()
        );
    }

    public function testSerialize()
    {

        $id = ProfileId::fromString('1');
        $email = Email::fromString('d.a.badura@gmail.com');

        $event = ProfileCreated::raise($id, $email);

        $beforeRecording = new DateTimeImmutable(date(DateTimeImmutable::ATOM));
        $recordedEvent = $event->recordNow(0);
        $afterRecording = new DateTimeImmutable(date(DateTimeImmutable::ATOM));

        $serializedEvent = $recordedEvent->serialize();

        self::assertCount(5, $serializedEvent);

        self::assertArrayHasKey('aggregateId', $serializedEvent);
        self::assertEquals('1', $serializedEvent['aggregateId']);

        self::assertArrayHasKey('playhead', $serializedEvent);
        self::assertEquals(0, $serializedEvent['playhead']);

        self::assertArrayHasKey('event', $serializedEvent);
        self::assertEquals(
            'Patchlevel\EventSourcing\Tests\Unit\Fixture\ProfileCreated',
            $serializedEvent['event']
        );

        self::assertArrayHasKey('payload', $serializedEvent);
        self::assertEquals('{"profileId":"1","email":"d.a.badura@gmail.com"}', $serializedEvent['payload']);

        self::assertArrayHasKey('recordedOn', $serializedEvent);
        self::assertDateTimeImmutableBetween(
            $beforeRecording,
            $afterRecording,
            new DateTimeImmutable($serializedEvent['recordedOn']),
        );
    }

    public function testDeserialize()
    {
        $id = ProfileId::fromString('1');
        $email = Email::fromString('d.a.badura@gmail.com');

        $event = ProfileCreated::deserialize([
            'aggregateId' => '1',
            'playhead' => 0,
            'event' => 'Patchlevel\EventSourcing\Tests\Unit\Fixture\ProfileCreated',
            'payload' => '{"profileId":"1","email":"d.a.badura@gmail.com"}',
            'recordedOn' => '2020-11-20 13:57:49',
        ]);

        self::assertEquals($id, $event->profileId());
        self::assertEquals($email, $event->email());
        self::assertEquals(0, $event->playhead());
        self::assertInstanceOf(DateTimeImmutable::class, $event->recordedOn());
        self::assertEquals(
            [
                'profileId' => '1',
                'email' => 'd.a.badura@gmail.com',
            ],
            $event->payload()
        );
    }

    public function testDeserializeClassNotFound()
    {
        $this->expectException(AggregateException::class);

        ProfileCreated::deserialize([
            'aggregateId' => '1',
            'playhead' => 0,
            'event' => 'Patchlevel\EventSourcing\Tests\Unit\Fixture\NotFound',
            'payload' => '{"profileId":"1","email":"d.a.badura@gmail.com"}',
            'recordedOn' => '2020-11-20 13:57:49',
        ]);
    }

    public function testDeserializeAndSerialize()
    {
        $id = ProfileId::fromString('1');
        $email = Email::fromString('d.a.badura@gmail.com');

        $event = ProfileCreated::raise($id, $email);
        $recordedEvent = $event->recordNow(0);

        $serializedEvent = $recordedEvent->serialize();
        $event = ProfileCreated::deserialize($serializedEvent);

        self::assertEquals($id, $event->profileId());
        self::assertEquals($email, $event->email());
        self::assertEquals(0, $event->playhead());
        self::assertInstanceOf(DateTimeImmutable::class, $event->recordedOn());
        self::assertEquals(
            [
                'profileId' => '1',
                'email' => 'd.a.badura@gmail.com',
            ],
            $event->payload()
        );
    }

    private static function assertDateTimeImmutableBetween(
        DateTimeImmutable $fromExpected,
        DateTimeImmutable $toExpected,
        DateTimeImmutable $actual
    ): void {
        self::assertGreaterThanOrEqual($fromExpected, $actual);
        self::assertLessThanOrEqual($toExpected, $actual);
    }
}
