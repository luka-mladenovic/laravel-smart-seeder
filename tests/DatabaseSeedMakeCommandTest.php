<?php
namespace Tests;

use Mockery as m;
use Illuminate\Support\Composer;
use Illuminate\Foundation\Application;
use Lukam\SmartSeeder\Seeds\SeedCreator;
use Lukam\SmartSeeder\Console\SeedMakeCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class DatabaseSeedMakeCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testBasicCreateDumpsAutoload()
    {
        $command = new SeedMakeCommand(
            $creator = m::mock(SeedCreator::class),
            $composer = m::mock(Composer::class)
        );
        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->shouldReceive('create')->once()->with('create_foo', __DIR__.DIRECTORY_SEPARATOR.'seeds');
        $composer->shouldReceive('dumpAutoloads')->once();

        $this->runCommand($command, ['name' => 'create_foo']);
    }

    public function testBasicCreateGivesCreatorProperArguments()
    {
        $command = new SeedMakeCommand(
            $creator = m::mock(SeedCreator::class),
            m::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->shouldReceive('create')->once()->with('create_foo', __DIR__.DIRECTORY_SEPARATOR.'seeds');

        $this->runCommand($command, ['name' => 'create_foo']);
    }

    public function testBasicCreateGivesCreatorProperArgumentsWhenNameIsStudlyCase()
    {
        $command = new SeedMakeCommand(
            $creator = m::mock(SeedCreator::class),
            m::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->shouldReceive('create')->once()->with('create_foo', __DIR__.DIRECTORY_SEPARATOR.'seeds');

        $this->runCommand($command, ['name' => 'CreateFoo']);
    }

    public function testCanSpecifyPathToCreateMigrationsIn()
    {
        $command = new SeedMakeCommand(
            $creator = m::mock(SeedCreator::class),
            m::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $command->setLaravel($app);
        $app->setBasePath('/home/laravel');
        $creator->shouldReceive('create')->once()->with('create_foo', '/home/laravel/vendor/laravel-package/seeds');
        $this->runCommand($command, ['name' => 'create_foo', '--path' => 'vendor/laravel-package/seeds']);
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new ArrayInput($input), new NullOutput);
    }
}
