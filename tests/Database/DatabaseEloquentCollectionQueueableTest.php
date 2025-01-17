<?php

namespace WpStarter\Tests\Database;

use WpStarter\Database\Eloquent\Collection;
use WpStarter\Database\Eloquent\Model;
use WpStarter\Database\Eloquent\Relations\Pivot;
use Mockery;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentCollectionQueueableTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testSerializesPivotsEntitiesId()
    {
        $spy = Mockery::spy(Pivot::class);

        $c = new Collection([$spy]);

        $c->getQueueableIds();

        $spy->shouldHaveReceived()
            ->getQueueableId()
            ->once();
    }

    public function testSerializesModelEntitiesById()
    {
        $spy = Mockery::spy(Model::class);

        $c = new Collection([$spy]);

        $c->getQueueableIds();

        $spy->shouldHaveReceived()
            ->getQueueableId()
            ->once();
    }

    /**
     * @throws \Exception
     */
    public function testJsonSerializationOfCollectionQueueableIdsWorks()
    {
        // When the ID of a Model is binary instead of int or string, the Collection
        // serialization + JSON encoding breaks because of UTF-8 issues. Encoding
        // of a QueueableCollection must favor QueueableEntity::queueableId().
        $mock = Mockery::mock(Model::class, [
            'getKey' => random_bytes(10),
            'getQueueableId' => 'mocked',
        ]);

        $c = new Collection([$mock]);

        $payload = [
            'ids' => $c->getQueueableIds(),
        ];

        $this->assertNotFalse(
            json_encode($payload),
            'EloquentCollection is not using the QueueableEntity::getQueueableId() method.'
        );
    }
}
