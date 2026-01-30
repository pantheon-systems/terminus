<?php

declare(strict_types=1);

namespace Pantheon\Terminus\Tests\Unit\Models;

use Pantheon\Terminus\Models\TerminusModel;
use Pantheon\Terminus\Tests\Unit\UnitTestCase;

/**
 * Concrete implementation for testing the abstract TerminusModel
 */
class ConcreteTestModel extends TerminusModel
{
    public const PRETTY_NAME = 'test model';

    protected $url = 'sites/{id}';

    public static $date_attributes = ['created_at', 'updated_at'];
}

/**
 * @covers \Pantheon\Terminus\Models\TerminusModel
 */
class TerminusModelTest extends UnitTestCase
{
    /**
     * @test
     */
    public function constructorSetsIdFromAttributes(): void
    {
        $attributes = (object)['id' => 'test-id-123', 'name' => 'Test Model'];

        $model = new ConcreteTestModel($attributes);

        $this->assertEquals('test-id-123', $model->id);
    }

    /**
     * @test
     */
    public function constructorHandlesNullAttributes(): void
    {
        $model = new ConcreteTestModel(null);

        $this->assertNull($model->id);
    }

    /**
     * @test
     */
    public function constructorHandlesArrayAttributes(): void
    {
        $model = new ConcreteTestModel(['key' => 'value']);

        // Array attributes are cast to empty object
        $this->assertNull($model->id);
    }

    /**
     * @test
     */
    public function getReturnsAttributeValue(): void
    {
        $attributes = (object)[
            'id' => 'test-id',
            'name' => 'Test Name',
            'status' => 'active',
        ];

        $model = new ConcreteTestModel($attributes);

        $this->assertEquals('Test Name', $model->get('name'));
        $this->assertEquals('active', $model->get('status'));
    }

    /**
     * @test
     */
    public function getReturnsNullForMissingAttribute(): void
    {
        $attributes = (object)['id' => 'test-id'];

        $model = new ConcreteTestModel($attributes);

        $this->assertNull($model->get('nonexistent'));
    }

    /**
     * @test
     */
    public function hasReturnsTrueForExistingAttribute(): void
    {
        $attributes = (object)['id' => 'test-id', 'name' => 'Test'];

        $model = new ConcreteTestModel($attributes);

        $this->assertTrue($model->has('name'));
        $this->assertTrue($model->has('id'));
    }

    /**
     * @test
     */
    public function hasReturnsFalseForMissingAttribute(): void
    {
        $attributes = (object)['id' => 'test-id'];

        $model = new ConcreteTestModel($attributes);

        $this->assertFalse($model->has('nonexistent'));
    }

    /**
     * @test
     */
    public function setSetsAttributeValue(): void
    {
        $model = new ConcreteTestModel((object)['id' => 'test']);

        $model->set('custom', 'value');

        $this->assertEquals('value', $model->get('custom'));
    }

    /**
     * @test
     */
    public function setOverwritesExistingValue(): void
    {
        $model = new ConcreteTestModel((object)['id' => 'test', 'name' => 'Original']);

        $model->set('name', 'Updated');

        $this->assertEquals('Updated', $model->get('name'));
    }

    /**
     * @test
     */
    public function unsetAttributeRemovesAttribute(): void
    {
        $model = new ConcreteTestModel((object)['id' => 'test', 'name' => 'Test']);

        $model->unsetAttribute('name');

        $this->assertFalse($model->has('name'));
        $this->assertNull($model->get('name'));
    }

    /**
     * @test
     */
    public function serializeReturnsAttributesAsArray(): void
    {
        $attributes = (object)[
            'id' => 'test-id',
            'name' => 'Test Name',
            'count' => 42,
        ];

        $model = new ConcreteTestModel($attributes);
        $serialized = $model->serialize();

        $this->assertIsArray($serialized);
        $this->assertEquals('test-id', $serialized['id']);
        $this->assertEquals('Test Name', $serialized['name']);
        $this->assertEquals(42, $serialized['count']);
    }

    /**
     * @test
     */
    public function getUrlReplacesIdPlaceholder(): void
    {
        $model = new ConcreteTestModel((object)['id' => 'site-abc-123']);

        $url = $model->getUrl();

        $this->assertEquals('sites/site-abc-123', $url);
    }

    /**
     * @test
     */
    public function getUrlHandlesNullId(): void
    {
        $model = new ConcreteTestModel(null);

        $url = $model->getUrl();

        $this->assertEquals('sites/', $url);
    }

    /**
     * @test
     */
    public function getReferencesReturnsIdArray(): void
    {
        $model = new ConcreteTestModel((object)['id' => 'ref-123']);

        $references = $model->getReferences();

        $this->assertIsArray($references);
        $this->assertContains('ref-123', $references);
    }

    /**
     * @test
     */
    public function prettyNameConstantIsSet(): void
    {
        $this->assertEquals('test model', ConcreteTestModel::PRETTY_NAME);
    }

    /**
     * @test
     */
    public function dateAttributesStaticPropertyExists(): void
    {
        $this->assertEquals(['created_at', 'updated_at'], ConcreteTestModel::$date_attributes);
    }
}
