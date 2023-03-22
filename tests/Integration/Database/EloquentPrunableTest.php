<?php

namespace WpStarter\Tests\Integration\Database;

use WpStarter\Database\Eloquent\Model;
use WpStarter\Database\Eloquent\Prunable;
use WpStarter\Database\Eloquent\SoftDeletes;
use WpStarter\Database\Events\ModelsPruned;
use WpStarter\Database\Schema\Blueprint;
use WpStarter\Support\Facades\Event;
use WpStarter\Support\Facades\Schema;
use LogicException;

/** @group SkipMSSQL */
class EloquentPrunableTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        ws_collect([
            'prunable_test_models',
            'prunable_soft_delete_test_models',
            'prunable_test_model_missing_prunable_methods',
            'prunable_with_custom_prune_method_test_models',
        ])->each(function ($table) {
            Schema::create($table, function (Blueprint $table) {
                $table->increments('id');
                $table->softDeletes();
                $table->boolean('pruned')->default(false);
                $table->timestamps();
            });
        });
    }

    public function testPrunableMethodMustBeImplemented()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Please implement',
        );

        PrunableTestModelMissingPrunableMethod::create()->pruneAll();
    }

    public function testPrunesRecords()
    {
        Event::fake();

        ws_collect(range(1, 5000))->map(function ($id) {
            return ['id' => $id];
        })->chunk(200)->each(function ($chunk) {
            PrunableTestModel::insert($chunk->all());
        });

        $count = (new PrunableTestModel)->pruneAll();

        $this->assertEquals(1500, $count);
        $this->assertEquals(3500, PrunableTestModel::count());

        Event::assertDispatched(ModelsPruned::class, 2);
    }

    public function testPrunesSoftDeletedRecords()
    {
        Event::fake();

        ws_collect(range(1, 5000))->map(function ($id) {
            return ['id' => $id, 'deleted_at' => ws_now()];
        })->chunk(200)->each(function ($chunk) {
            PrunableSoftDeleteTestModel::insert($chunk->all());
        });

        $count = (new PrunableSoftDeleteTestModel)->pruneAll();

        $this->assertEquals(3000, $count);
        $this->assertEquals(0, PrunableSoftDeleteTestModel::count());
        $this->assertEquals(2000, PrunableSoftDeleteTestModel::withTrashed()->count());

        Event::assertDispatched(ModelsPruned::class, 3);
    }

    public function testPruneWithCustomPruneMethod()
    {
        Event::fake();

        ws_collect(range(1, 5000))->map(function ($id) {
            return ['id' => $id];
        })->chunk(200)->each(function ($chunk) {
            PrunableWithCustomPruneMethodTestModel::insert($chunk->all());
        });

        $count = (new PrunableWithCustomPruneMethodTestModel)->pruneAll();

        $this->assertEquals(1000, $count);
        $this->assertTrue((bool) PrunableWithCustomPruneMethodTestModel::first()->pruned);
        $this->assertFalse((bool) PrunableWithCustomPruneMethodTestModel::orderBy('id', 'desc')->first()->pruned);
        $this->assertEquals(5000, PrunableWithCustomPruneMethodTestModel::count());

        Event::assertDispatched(ModelsPruned::class, 1);
    }
}

class PrunableTestModel extends Model
{
    use Prunable;

    public function prunable()
    {
        return $this->where('id', '<=', 1500);
    }
}

class PrunableSoftDeleteTestModel extends Model
{
    use Prunable, SoftDeletes;

    public function prunable()
    {
        return $this->where('id', '<=', 3000);
    }
}

class PrunableWithCustomPruneMethodTestModel extends Model
{
    use Prunable;

    public function prunable()
    {
        return $this->where('id', '<=', 1000);
    }

    public function prune()
    {
        $this->forceFill([
            'pruned' => true,
        ])->save();
    }
}

class PrunableTestModelMissingPrunableMethod extends Model
{
    use Prunable;
}
