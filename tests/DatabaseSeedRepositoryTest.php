<?php
namespace Tests;

use Closure;
use stdClass;
use Mockery as m;
use Illuminate\Support\Collection;
use Illuminate\Database\Connection;
use Lukam\SmartSeeder\Seeds\DatabaseSeedRepository;
use Illuminate\Database\ConnectionResolverInterface;

class DatabaseSeedRepositoryTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testGetRanSeedsListSeedsByPackage()
    {
        $repo = $this->getRepository();
        $query = m::mock(\stdClass::class);
        $connectionMock = m::mock(Connection::class);
        $repo->getConnectionResolver()->shouldReceive('connection')->with(null)->andReturn($connectionMock);
        $repo->getConnection()->shouldReceive('table')->once()->with('seeds')->andReturn($query);
        $query->shouldReceive('orderBy')->once()->with('batch', 'asc')->andReturn($query);
        $query->shouldReceive('orderBy')->once()->with('seed', 'asc')->andReturn($query);
        $query->shouldReceive('pluck')->once()->with('seed')->andReturn(new Collection(['bar']));
        $query->shouldReceive('useWritePdo')->once()->andReturn($query);

        $this->assertEquals(['bar'], $repo->getRan());
    }

    public function testGetLastSeedsGetsAllSeedsWithTheLatestBatchNumber()
    {
        $repo = $this->getMockBuilder(DatabaseSeedRepository::class)->setMethods(['getLastBatchNumber'])->setConstructorArgs([
            $resolver = m::mock(ConnectionResolverInterface::class), 'seeds',
        ])->getMock();
        $repo->expects($this->once())->method('getLastBatchNumber')->will($this->returnValue(1));
        $query = m::mock(\stdClass::class);
        $connectionMock = m::mock(Connection::class);
        $repo->getConnectionResolver()->shouldReceive('connection')->with(null)->andReturn($connectionMock);
        $repo->getConnection()->shouldReceive('table')->once()->with('seeds')->andReturn($query);
        $query->shouldReceive('where')->once()->with('batch', 1)->andReturn($query);
        $query->shouldReceive('orderBy')->once()->with('seed', 'desc')->andReturn($query);
        $query->shouldReceive('get')->once()->andReturn(new Collection(['foo']));
        $query->shouldReceive('useWritePdo')->once()->andReturn($query);

        $this->assertEquals(['foo'], $repo->getLast());
    }

    public function testLogMethodInsertsRecordIntoSeedTable()
    {
        $repo = $this->getRepository();
        $query = m::mock(\stdClass::class);
        $connectionMock = m::mock(Connection::class);
        $repo->getConnectionResolver()->shouldReceive('connection')->with(null)->andReturn($connectionMock);
        $repo->getConnection()->shouldReceive('table')->once()->with('seeds')->andReturn($query);
        $query->shouldReceive('insert')->once()->with(['seed' => 'bar', 'batch' => 1]);
        $query->shouldReceive('useWritePdo')->once()->andReturn($query);

        $repo->log('bar', 1);
    }

    public function testDeleteMethodRemovesASeedFromTheTable()
    {
        $repo = $this->getRepository();
        $query = m::mock(stdClass::class);
        $connectionMock = m::mock(Connection::class);
        $repo->getConnectionResolver()->shouldReceive('connection')->with(null)->andReturn($connectionMock);
        $repo->getConnection()->shouldReceive('table')->once()->with('seeds')->andReturn($query);
        $query->shouldReceive('where')->once()->with('seed', 'foo')->andReturn($query);
        $query->shouldReceive('delete')->once();
        $query->shouldReceive('useWritePdo')->once()->andReturn($query);
        $seed = (object) ['seed' => 'foo'];

        $repo->delete($seed);
    }

    public function testGetNextBatchNumberReturnsLastBatchNumberPlusOne()
    {
        $repo = $this->getMockBuilder(DatabaseSeedRepository::class)->setMethods(['getLastBatchNumber'])->setConstructorArgs([
            m::mock(ConnectionResolverInterface::class), 'seeds',
        ])->getMock();
        $repo->expects($this->once())->method('getLastBatchNumber')->will($this->returnValue(1));

        $this->assertEquals(2, $repo->getNextBatchNumber());
    }

    public function testGetLastBatchNumberReturnsMaxBatch()
    {
        $repo = $this->getRepository();
        $query = m::mock(stdClass::class);
        $connectionMock = m::mock(Connection::class);
        $repo->getConnectionResolver()->shouldReceive('connection')->with(null)->andReturn($connectionMock);
        $repo->getConnection()->shouldReceive('table')->once()->with('seeds')->andReturn($query);
        $query->shouldReceive('max')->once()->andReturn(1);
        $query->shouldReceive('useWritePdo')->once()->andReturn($query);

        $this->assertEquals(1, $repo->getLastBatchNumber());
    }

    public function testCreateRepositoryCreatesProperDatabaseTable()
    {
        $repo = $this->getRepository();
        $schema = m::mock(stdClass::class);
        $connectionMock = m::mock(Connection::class);
        $repo->getConnectionResolver()->shouldReceive('connection')->with(null)->andReturn($connectionMock);
        $repo->getConnection()->shouldReceive('getSchemaBuilder')->once()->andReturn($schema);
        $schema->shouldReceive('create')->once()->with('seeds', m::type(Closure::class));

        $repo->createRepository();
    }

    protected function getRepository()
    {
        return new DatabaseSeedRepository(m::mock(ConnectionResolverInterface::class), 'seeds');
    }
}
