<?php

namespace Tests;

use Mockery as m;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Illuminate\Filesystem\Filesystem;
use Lukam\SmartSeeder\Seeds\SeedCreator;

class DatabaseSeedCreatorTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    protected function getCreator()
    {
        $files = m::mock(Filesystem::class);

        return $this->getMockBuilder(SeedCreator::class)->setMethods(null)->setConstructorArgs([$files])->getMock();
    }

    public function testBasicCreateMethodStoresSeedFile()
    {
        $creator = $this->getCreator();

        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath().'/blank.stub')->andReturn('DummyClass');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/create_bar.php', 'CreateBar');

        $creator->create('create_bar', 'foo');
    }

    public function testTableUpdateSeedWontCreateDuplicateClass()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A SeedCreatorFakeSeed class already exists.');

        $creator = $this->getCreator();

        $creator->create('seed_creator_fake_seed', 'foo');
    }
}
