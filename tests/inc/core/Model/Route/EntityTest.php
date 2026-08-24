<?php

namespace Runalyze\Model\Route;

class EntityTest extends \PHPUnit\Framework\TestCase
{
    protected function simpleObject(): \Runalyze\Model\Route\Entity
    {
        return new Entity([
            Entity::NAME => 'Test route',
            Entity::CITIES => 'City A - City B',
            Entity::DISTANCE => 3.14,
            Entity::ELEVATION => 20,
            Entity::ELEVATION_UP => 20,
            Entity::ELEVATION_DOWN => 15,
            Entity::GEOHASHES => ['u1xjhpfe7yvs', 'u1xjhzdtjx62'],
            Entity::ELEVATIONS_ORIGINAL => [195, 210],
            Entity::ELEVATIONS_CORRECTED => [200, 220],
            Entity::ELEVATIONS_SOURCE => 'unknown',
            Entity::IN_ROUTENET => 1,
        ]);
    }

    public function testEmptyObject()
    {
        $T = new Entity();

        $this->assertFalse($T->hasPositionData());
        $this->assertFalse($T->has(Entity::NAME));
        $this->assertFalse($T->has(Entity::DISTANCE));
        $this->assertFalse($T->inRoutenet());
    }

    public function testSimpleObject()
    {
        $T = $this->simpleObject();

        $this->assertEquals('Test route', $T->name());
        $this->assertEquals(['City A', 'City B'], $T->citiesAsArray());
        $this->assertEquals(3.14, $T->distance());
        $this->assertEquals(20, $T->elevation());
        $this->assertEquals(20, $T->elevationUp());
        $this->assertEquals(15, $T->elevationDown());
        $this->assertEquals(['u1xjhpfe7yvs', 'u1xjhzdtjx62'], $T->geohashes());
        $this->assertEquals([195, 210], $T->elevationsOriginal());
        $this->assertEquals([200, 220], $T->elevationsCorrected());
        $this->assertEquals('unknown', $T->get(Entity::ELEVATIONS_SOURCE));
        $this->assertTrue($T->inRoutenet());
    }

    public function testSynchronization()
    {
        $T = $this->simpleObject();
        $T->synchronize();
        $T->forceToSetMinMaxFromGeohashes();
        $this->assertEquals('u1xjhpfe7y', $T->get(Entity::STARTPOINT));
        $this->assertEquals('u1xjhzdtjx', $T->get(Entity::ENDPOINT));

        $this->assertEquals('u1xjhpdt5z', $T->get(Entity::MIN));
        $this->assertEquals('u1xjhzfemw', $T->get(Entity::MAX));
    }

    /**
     * @see https://github.com/Runalyze/Runalyze/issues/1172
     */
    public function testPossibilityOfTooLargeCorrectedElevations()
    {
        $Object = new Entity([
            Entity::GEOHASHES => ['u1xjhxf507s1', 'u1xjhxf6b7s9', 'u1xjhxfd8jyw', 'u1xjhxfdx0cw', 'u1xjhxffrhw4', 'u1xjhxg4r0du', 'u1xjhxg6p6bq', 'u1xjhxgdn0fk', 'u1xjhxgcvgh0', 'u1xjhxu1tytn', 'u1xjhxu3s0j8'],
            Entity::ELEVATIONS_ORIGINAL => [240, 238, 240, 238, 238, 237, 236, 237, 240, 248, 259],
            Entity::ELEVATIONS_CORRECTED => [240, 240, 240, 240, 240, 237, 237, 237, 237, 237, 259, 259, 259, 259, 259],
        ]);

        $this->assertEquals(11, $Object->num());
        $this->assertEquals(11, count($Object->elevationsCorrected()));
    }

    public function testSetLatitudesLongitudes()
    {
        $Object = new Entity();
        $Object->setLatitudesLongitudes([47.7, 47.8], [7.8, 7.7]);
        $Object->forceToSetMinMaxFromGeohashes();

        $this->assertEquals(['u0mx37xb9hmx', 'u0mrzjwzpjb4'], $Object->get(Entity::GEOHASHES));
        $this->assertEquals('u0mrr5wbxh', $Object->get(Entity::MIN));
        $this->assertEquals('u0mxcmxz1j', $Object->get(Entity::MAX));

    }

    public function testThatSetLatitudesLongitudesMustHaveExpectedSize()
    {
        $this->expectException(\RuntimeException::class);
        $Object = new Entity([
            Entity::ELEVATIONS_ORIGINAL => [240, 238, 240],
            Entity::ELEVATIONS_CORRECTED => [240, 240, 240],
        ]);
        $Object->setLatitudesLongitudes([47.7, 47.8, 47.7, 47.8], [7.8, 7.7, 7.8, 7.7]);
        $Object->forceToSetMinMaxFromGeohashes();
    }

    public function testEmptyArraysFromTrainingForm()
    {
        $Object = new Entity();
        $Object->setLatitudesLongitudes([''], ['']);

        $this->assertEquals(['7zzzzzzzzzzz'], $Object->get(Entity::GEOHASHES));
    }

    public function testEmptyLocationsSynchronized()
    {
        $Object = new Entity();
        $Object->setLatitudesLongitudes(['0.0', '0.0', '0.0'], ['0.0', '0.0', '0.0']);
        $Object->synchronize();

        $this->assertEquals([], $Object->get(Entity::GEOHASHES));
        $this->assertTrue($Object->isEmpty());
    }

    public function testSynchronizationOfStartAndEndpoint()
    {
        $Object = new Entity([]);
        $Object->setLatitudesLongitudes(
            [0.0, 0.0, 47.7, 47.8, 47.7, 47.8],
            [0.0, 0.0, 7.8, 7.7, 7.8, 7.7]
        );
        $Object->forceToSetMinMaxFromGeohashes();
        $Object->synchronize();

        $this->assertEquals('u0mx37xb9h', $Object->get(Entity::STARTPOINT));
        $this->assertEquals('u0mrzjwzpj', $Object->get(Entity::ENDPOINT));
        $this->assertEquals('u0mrr5wbxh', $Object->get(Entity::MIN));
        $this->assertEquals('u0mxcmxz1j', $Object->get(Entity::MAX));
    }
}
