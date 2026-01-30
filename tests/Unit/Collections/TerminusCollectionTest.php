<?php

declare(strict_types=1);

namespace Pantheon\Terminus\Tests\Unit\Collections;

use Pantheon\Terminus\Collections\TerminusCollection;
use Pantheon\Terminus\Models\TerminusModel;
use Pantheon\Terminus\Tests\Unit\UnitTestCase;

/**
 * Concrete implementation for testing the abstract TerminusCollection
 */
class ConcreteTestCollection extends TerminusCollection
{
    protected $collected_class = ConcreteCollectionTestModel::class;

    // Override all() to not call fetch() for simpler testing
    public function all()
    {
        if (is_null($this->models)) {
            $this->models = [];
        }
        return $this->models;
    }

    // Expose models for testing
    public function setModels(array $models): void
    {
        $this->models = $models;
    }
}

/**
 * Concrete model for testing
 */
class ConcreteCollectionTestModel extends TerminusModel
{
    public const PRETTY_NAME = 'test item';
}

/**
 * @covers \Pantheon\Terminus\Collections\TerminusCollection
 */
class TerminusCollectionTest extends UnitTestCase
{
    private ConcreteTestCollection $collection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->collection = new ConcreteTestCollection();
    }

    /**
     * @test
     */
    public function constructorSetsDataFromOptions(): void
    {
        $data = ['item1' => (object)['id' => 'item1'], 'item2' => (object)['id' => 'item2']];

        $collection = new ConcreteTestCollection(['data' => $data]);

        $this->assertEquals($data, $collection->getData());
    }

    /**
     * @test
     */
    public function getDataReturnsEmptyArrayByDefault(): void
    {
        $this->assertEquals([], $this->collection->getData());
    }

    /**
     * @test
     */
    public function setDataSetsData(): void
    {
        $data = ['key' => 'value'];

        $this->collection->setData($data);

        $this->assertEquals($data, $this->collection->getData());
    }

    /**
     * @test
     */
    public function splitStringParsesCommaSeparatedValues(): void
    {
        $result = $this->collection->splitString('one, two, three');

        $this->assertEquals(['one', 'two', 'three'], $result);
    }

    /**
     * @test
     */
    public function splitStringTrimsWhitespace(): void
    {
        $result = $this->collection->splitString('  spaced  ,  values  ');

        $this->assertEquals(['spaced', 'values'], $result);
    }

    /**
     * @test
     */
    public function splitStringReturnsEmptyArrayForEmptyString(): void
    {
        $result = $this->collection->splitString('');

        $this->assertEquals([], $result);
    }

    /**
     * @test
     */
    public function splitStringHandlesSingleValue(): void
    {
        $result = $this->collection->splitString('single');

        $this->assertEquals(['single'], $result);
    }

    /**
     * @test
     */
    public function getCollectedClassReturnsClassName(): void
    {
        $this->assertEquals(
            ConcreteCollectionTestModel::class,
            $this->collection->getCollectedClass()
        );
    }

    /**
     * @test
     */
    public function hasReturnsFalseForEmptyCollection(): void
    {
        $this->assertFalse($this->collection->has('nonexistent'));
    }

    /**
     * @test
     */
    public function idsReturnsEmptyArrayForEmptyCollection(): void
    {
        $this->assertEquals([], $this->collection->ids());
    }

    /**
     * @test
     */
    public function containsNoneReturnsTrueForEmptyCollection(): void
    {
        $this->assertTrue($this->collection->containsNone());
    }

    /**
     * @test
     */
    public function containsAnyReturnsFalseForEmptyCollection(): void
    {
        $this->assertFalse($this->collection->containsAny(['id1', 'id2']));
    }

    /**
     * @test
     */
    public function containsAllReturnsFalseForEmptyCollection(): void
    {
        $this->assertFalse($this->collection->containsAll(['id1']));
    }

    /**
     * @test
     */
    public function containsAllReturnsTrueForEmptyIdList(): void
    {
        $this->assertTrue($this->collection->containsAll([]));
    }

    /**
     * @test
     */
    public function resetClearsModels(): void
    {
        // Access all() to initialize models
        $this->collection->all();

        // Reset
        $result = $this->collection->reset();

        // Should return $this for chaining
        $this->assertSame($this->collection, $result);
    }

    /**
     * @test
     */
    public function serializeReturnsEmptyArrayForEmptyCollection(): void
    {
        $this->assertEquals([], $this->collection->serialize());
    }

    /**
     * @test
     */
    public function hasReturnsTrueWhenModelExists(): void
    {
        $model = $this->createMock(ConcreteCollectionTestModel::class);
        $this->collection->setModels(['test-id' => $model]);

        $this->assertTrue($this->collection->has('test-id'));
    }

    /**
     * @test
     */
    public function containsNoneReturnsFalseWhenModelsExist(): void
    {
        $model = $this->createMock(ConcreteCollectionTestModel::class);
        $this->collection->setModels(['test-id' => $model]);

        $this->assertFalse($this->collection->containsNone());
    }

    /**
     * @test
     */
    public function idsReturnsModelIds(): void
    {
        $model1 = $this->createMock(ConcreteCollectionTestModel::class);
        $model2 = $this->createMock(ConcreteCollectionTestModel::class);
        $this->collection->setModels([
            'id-1' => $model1,
            'id-2' => $model2,
        ]);

        $ids = $this->collection->ids();

        $this->assertContains('id-1', $ids);
        $this->assertContains('id-2', $ids);
    }

    /**
     * @test
     */
    public function containsAnyReturnsTrueWhenAnyIdMatches(): void
    {
        $model = $this->createMock(ConcreteCollectionTestModel::class);
        $this->collection->setModels(['id-1' => $model]);

        $this->assertTrue($this->collection->containsAny(['id-1', 'id-2', 'id-3']));
    }

    /**
     * @test
     */
    public function containsAllReturnsTrueWhenAllIdsMatch(): void
    {
        $model1 = $this->createMock(ConcreteCollectionTestModel::class);
        $model2 = $this->createMock(ConcreteCollectionTestModel::class);
        $this->collection->setModels([
            'id-1' => $model1,
            'id-2' => $model2,
        ]);

        $this->assertTrue($this->collection->containsAll(['id-1', 'id-2']));
    }

    /**
     * @test
     */
    public function containsAllReturnsFalseWhenSomeIdsMissing(): void
    {
        $model = $this->createMock(ConcreteCollectionTestModel::class);
        $this->collection->setModels(['id-1' => $model]);

        $this->assertFalse($this->collection->containsAll(['id-1', 'id-2']));
    }
}
