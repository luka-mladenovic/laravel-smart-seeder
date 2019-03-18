<?php
namespace Tests;

use Mockery as m;
use InvalidArgumentException;
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

        return $this->getMockBuilder(SeedCreator::class)->setMethods(['getDatePrefix'])->setConstructorArgs([$files])->getMock();
    }

    public function testBasicCreateMethodStoresSeedFile()
    {
        $creator = $this->getCreator();

        $creator->expects($this->any())->method('getDatePrefix')->will($this->returnValue('date'));
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath().'/blank.stub')->andReturn('DummyClass');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/date_create_bar.php', 'CreateBar');

        $creator->create('create_bar', 'foo');
    }
}
