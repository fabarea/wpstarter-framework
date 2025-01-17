<?php

namespace WpStarter\Tests\Database;

use WpStarter\Database\ConnectionInterface;
use WpStarter\Database\Eloquent\Builder as EloquentBuilder;
use WpStarter\Database\Eloquent\Model;
use WpStarter\Database\Eloquent\SoftDeletingScope;
use WpStarter\Database\Query\Builder as BaseBuilder;
use WpStarter\Database\Query\Grammars\Grammar;
use WpStarter\Database\Query\Processors\Processor;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;

class DatabaseSoftDeletingScopeTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testApplyingScopeToABuilder()
    {
        $scope = m::mock(SoftDeletingScope::class.'[extend]');
        $builder = m::mock(EloquentBuilder::class);
        $model = m::mock(Model::class);
        $model->shouldReceive('getQualifiedDeletedAtColumn')->once()->andReturn('table.deleted_at');
        $builder->shouldReceive('whereNull')->once()->with('table.deleted_at');

        $scope->apply($builder, $model);
    }

    public function testRestoreExtension()
    {
        $builder = new EloquentBuilder(new BaseBuilder(
            m::mock(ConnectionInterface::class),
            m::mock(Grammar::class),
            m::mock(Processor::class)
        ));
        $scope = new SoftDeletingScope;
        $scope->extend($builder);
        $callback = $builder->getMacro('restore');
        $givenBuilder = m::mock(EloquentBuilder::class);
        $givenBuilder->shouldReceive('withTrashed')->once();
        $givenBuilder->shouldReceive('getModel')->once()->andReturn($model = m::mock(stdClass::class));
        $model->shouldReceive('getDeletedAtColumn')->once()->andReturn('deleted_at');
        $givenBuilder->shouldReceive('update')->once()->with(['deleted_at' => null]);

        $callback($givenBuilder);
    }

    public function testWithTrashedExtension()
    {
        $builder = new EloquentBuilder(new BaseBuilder(
            m::mock(ConnectionInterface::class),
            m::mock(Grammar::class),
            m::mock(Processor::class)
        ));
        $scope = m::mock(SoftDeletingScope::class.'[remove]');
        $scope->extend($builder);
        $callback = $builder->getMacro('withTrashed');
        $givenBuilder = m::mock(EloquentBuilder::class);
        $givenBuilder->shouldReceive('getModel')->andReturn($model = m::mock(Model::class));
        $givenBuilder->shouldReceive('withoutGlobalScope')->with($scope)->andReturn($givenBuilder);
        $result = $callback($givenBuilder);

        $this->assertEquals($givenBuilder, $result);
    }

    public function testOnlyTrashedExtension()
    {
        $builder = new EloquentBuilder(new BaseBuilder(
            m::mock(ConnectionInterface::class),
            m::mock(Grammar::class),
            m::mock(Processor::class)
        ));
        $model = m::mock(Model::class);
        $model->makePartial();
        $scope = m::mock(SoftDeletingScope::class.'[remove]');
        $scope->extend($builder);
        $callback = $builder->getMacro('onlyTrashed');
        $givenBuilder = m::mock(EloquentBuilder::class);
        $givenBuilder->shouldReceive('getQuery')->andReturn($query = m::mock(stdClass::class));
        $givenBuilder->shouldReceive('getModel')->andReturn($model);
        $givenBuilder->shouldReceive('withoutGlobalScope')->with($scope)->andReturn($givenBuilder);
        $model->shouldReceive('getQualifiedDeletedAtColumn')->andReturn('table.deleted_at');
        $givenBuilder->shouldReceive('whereNotNull')->once()->with('table.deleted_at');
        $result = $callback($givenBuilder);

        $this->assertEquals($givenBuilder, $result);
    }

    public function testWithoutTrashedExtension()
    {
        $builder = new EloquentBuilder(new BaseBuilder(
            m::mock(ConnectionInterface::class),
            m::mock(Grammar::class),
            m::mock(Processor::class)
        ));
        $model = m::mock(Model::class);
        $model->makePartial();
        $scope = m::mock(SoftDeletingScope::class.'[remove]');
        $scope->extend($builder);
        $callback = $builder->getMacro('withoutTrashed');
        $givenBuilder = m::mock(EloquentBuilder::class);
        $givenBuilder->shouldReceive('getQuery')->andReturn($query = m::mock(stdClass::class));
        $givenBuilder->shouldReceive('getModel')->andReturn($model);
        $givenBuilder->shouldReceive('withoutGlobalScope')->with($scope)->andReturn($givenBuilder);
        $model->shouldReceive('getQualifiedDeletedAtColumn')->andReturn('table.deleted_at');
        $givenBuilder->shouldReceive('whereNull')->once()->with('table.deleted_at');
        $result = $callback($givenBuilder);

        $this->assertEquals($givenBuilder, $result);
    }
}
