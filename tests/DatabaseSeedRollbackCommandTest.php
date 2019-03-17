<?php

namespace Illuminate\Tests\Database;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Lukam\SmartSeeder\Seeds\Seeder;
use Illuminate\Foundation\Application;
use Lukam\SmartSeeder\Console\RollbackCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class DatabaseSeedRollbackCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testRollbackCommandCallsSeederWithProperArguments()
    {
        $command = new RollbackCommand($seeder = m::mock(Seeder::class));
        $app = new ApplicationDatabaseRollbackStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $seeder->shouldReceive('paths')->once()->andReturn([]);
        $seeder->shouldReceive('setConnection')->once()->with(null);
        $seeder->shouldReceive('rollback')->once()->with([__DIR__.DIRECTORY_SEPARATOR.'seeds'], ['pretend' => false, 'step' => 0]);
        $seeder->shouldReceive('getNotes')->andReturn([]);

        $this->runCommand($command);
    }

    public function testRollbackCommandCallsseederWithStepOption()
    {
        $command = new RollbackCommand($seeder = m::mock(Seeder::class));
        $app = new ApplicationDatabaseRollbackStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $seeder->shouldReceive('paths')->once()->andReturn([]);
        $seeder->shouldReceive('setConnection')->once()->with(null);
        $seeder->shouldReceive('rollback')->once()->with([__DIR__.DIRECTORY_SEPARATOR.'seeds'], ['pretend' => false, 'step' => 2]);
        $seeder->shouldReceive('getNotes')->andReturn([]);

        $this->runCommand($command, ['--step' => 2]);
    }

    public function testRollbackCommandCanBePretended()
    {
        $command = new RollbackCommand($seeder = m::mock(Seeder::class));
        $app = new ApplicationDatabaseRollbackStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $seeder->shouldReceive('paths')->once()->andReturn([]);
        $seeder->shouldReceive('setConnection')->once()->with('foo');
        $seeder->shouldReceive('rollback')->once()->with([__DIR__.DIRECTORY_SEPARATOR.'seeds'], true);
        $seeder->shouldReceive('getNotes')->andReturn([]);

        $this->runCommand($command, ['--pretend' => true, '--database' => 'foo']);
    }

    public function testRollbackCommandCanBePretendedWithStepOption()
    {
        $command = new RollbackCommand($seeder = m::mock(Seeder::class));
        $app = new ApplicationDatabaseRollbackStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $seeder->shouldReceive('paths')->once()->andReturn([]);
        $seeder->shouldReceive('setConnection')->once()->with('foo');
        $seeder->shouldReceive('rollback')->once()->with([__DIR__.DIRECTORY_SEPARATOR.'seeds'], ['pretend' => true, 'step' => 2]);

        $seeder->shouldReceive('getNotes')->andReturn([]);

        $this->runCommand($command, ['--pretend' => true, '--database' => 'foo', '--step' => 2]);
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new ArrayInput($input), new NullOutput);
    }
}

class ApplicationDatabaseRollbackStub extends Application
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