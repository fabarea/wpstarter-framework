<?php

namespace WpStarter\Tests\Console\Scheduling;

use WpStarter\Console\Scheduling\CacheEventMutex;
use WpStarter\Console\Scheduling\Event;
use WpStarter\Contracts\Cache\Factory;
use WpStarter\Contracts\Cache\Repository;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class CacheEventMutexTest extends TestCase
{
    /**
     * @var \WpStarter\Console\Scheduling\CacheEventMutex
     */
    protected $cacheMutex;

    /**
     * @var \WpStarter\Console\Scheduling\Event
     */
    protected $event;

    /**
     * @var \WpStarter\Contracts\Cache\Factory
     */
    protected $cacheFactory;

    /**
     * @var \WpStarter\Contracts\Cache\Repository
     */
    protected $cacheRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheFactory = m::mock(Factory::class);
        $this->cacheRepository = m::mock(Repository::class);
        $this->cacheFactory->shouldReceive('store')->andReturn($this->cacheRepository);
        $this->cacheMutex = new CacheEventMutex($this->cacheFactory);
        $this->event = new Event($this->cacheMutex, 'command');
    }

    public function testPreventOverlap()
    {
        $this->cacheRepository->shouldReceive('add')->once();

        $this->cacheMutex->create($this->event);
    }

    public function testCustomConnection()
    {
        $this->cacheFactory->shouldReceive('store')->with('test')->andReturn($this->cacheRepository);
        $this->cacheRepository->shouldReceive('add')->once();
        $this->cacheMutex->useStore('test');

        $this->cacheMutex->create($this->event);
    }

    public function testPreventOverlapFails()
    {
        $this->cacheRepository->shouldReceive('add')->once()->andReturn(false);

        $this->assertFalse($this->cacheMutex->create($this->event));
    }

    public function testOverlapsForNonRunningTask()
    {
        $this->cacheRepository->shouldReceive('has')->once()->andReturn(false);

        $this->assertFalse($this->cacheMutex->exists($this->event));
    }

    public function testOverlapsForRunningTask()
    {
        $this->cacheRepository->shouldReceive('has')->once()->andReturn(true);

        $this->assertTrue($this->cacheMutex->exists($this->event));
    }

    public function testResetOverlap()
    {
        $this->cacheRepository->shouldReceive('forget')->once();

        $this->cacheMutex->forget($this->event);
    }
}
