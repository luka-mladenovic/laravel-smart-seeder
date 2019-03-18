<?php
namespace Tests;

use Mockery as m;
use Lukam\SmartSeeder\Seeds\Seeder;
use Illuminate\Foundation\Application;
use Lukam\SmartSeeder\Console\ResetCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class DatabaseSeedResetCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testResetCommandCallsSeederWithProperArguments()
    {
        $command = new ResetCommand($seeder = m::mock(Seeder::class));
        $app = new ApplicationDatabaseResetStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $seeder->shouldReceive('paths')->once()->andReturn([]);
        $seeder->shouldReceive('setConnection')->once()->with(null);
        $seeder->shouldReceive('repositoryExists')->once()->andReturn(true);
        $seeder->shouldReceive('reset')->once()->with([__DIR__.DIRECTORY_SEPARATOR.'seeds'], false);
        $seeder->shouldReceive('getNotes')->andReturn([]);

        $this->runCommand($command);
    }

    public function testResetCommandCanBePretended()
    {
        $command = new ResetCommand($seeder = m::mock(Seeder::class));
        $app = new ApplicationDatabaseResetStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $seeder->shouldReceive('paths')->once()->andReturn([]);
        $seeder->shouldReceive('setConnection')->once()->with('foo');
        $seeder->shouldReceive('repositoryExists')->once()->andReturn(true);
        $seeder->shouldReceive('reset')->once()->with([__DIR__.DIRECTORY_SEPARATOR.'seeds'], true);
        $seeder->shouldReceive('getNotes')->andReturn([]);

        $this->runCommand($command, ['--pretend' => true, '--database' => 'foo']);
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new ArrayInput($input), new NullOutput);
    }
}

class ApplicationDatabaseResetStub extends Application
{
    public function __construct(array $data = [])
    {
        foreach ($data as $abstract => $instance) {
            $this->instance($abstract, $instance);
        }
    }

    public function environment(...$environments)
    {
        return 'development';
    }
}
