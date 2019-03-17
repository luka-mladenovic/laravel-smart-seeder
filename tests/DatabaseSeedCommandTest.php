<?php

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Lukam\SmartSeeder\Seeds\Seeder;
use Illuminate\Foundation\Application;
use Lukam\SmartSeeder\Console\SeedCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class DatabaseSeedCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testBasicSeedsCallSeederWithProperArguments()
    {
        $command = new SeedCommand($seeder = m::mock(Seeder::class));
        $app = new ApplicationDatabaseSeedStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $seeder->shouldReceive('paths')->once()->andReturn([]);
        $seeder->shouldReceive('setConnection')->once()->with(null);
        $seeder->shouldReceive('run')->once()->with([__DIR__.DIRECTORY_SEPARATOR.'seeds'], ['pretend' => false, 'step' => false]);
        $seeder->shouldReceive('getNotes')->andReturn([]);
        $seeder->shouldReceive('repositoryExists')->once()->andReturn(true);

        $this->runCommand($command);
    }

    public function testSeedRepositoryCreatedWhenNecessary()
    {
        $params = [$seeder = m::mock(Seeder::class)];
        $command = $this->getMockBuilder(SeedCommand::class)->setMethods(['call'])->setConstructorArgs($params)->getMock();
        $app = new ApplicationDatabaseSeedStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $seeder->shouldReceive('paths')->once()->andReturn([]);
        $seeder->shouldReceive('setConnection')->once()->with(null);
        $seeder->shouldReceive('run')->once()->with([__DIR__.DIRECTORY_SEPARATOR.'seeds'], ['pretend' => false, 'step' => false]);
        $seeder->shouldReceive('getNotes')->andReturn([]);
        $seeder->shouldReceive('repositoryExists')->once()->andReturn(false);
        $command->expects($this->once())->method('call')->with($this->equalTo('seed:install'), $this->equalTo(['--database' => null]));

        $this->runCommand($command);
    }

    public function testTheCommandMayBePretended()
    {
        $command = new SeedCommand($seeder = m::mock(Seeder::class));
        $app = new ApplicationDatabaseSeedStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $seeder->shouldReceive('paths')->once()->andReturn([]);
        $seeder->shouldReceive('setConnection')->once()->with(null);
        $seeder->shouldReceive('run')->once()->with([__DIR__.DIRECTORY_SEPARATOR.'seeds'], ['pretend' => true, 'step' => false]);
        $seeder->shouldReceive('getNotes')->andReturn([]);
        $seeder->shouldReceive('repositoryExists')->once()->andReturn(true);

        $this->runCommand($command, ['--pretend' => true]);
    }

    public function testTheDatabaseMayBeSet()
    {
        $command = new SeedCommand($seeder = m::mock(Seeder::class));
        $app = new ApplicationDatabaseSeedStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $seeder->shouldReceive('paths')->once()->andReturn([]);
        $seeder->shouldReceive('setConnection')->once()->with('foo');
        $seeder->shouldReceive('run')->once()->with([__DIR__.DIRECTORY_SEPARATOR.'seeds'], ['pretend' => false, 'step' => false]);
        $seeder->shouldReceive('getNotes')->andReturn([]);
        $seeder->shouldReceive('repositoryExists')->once()->andReturn(true);

        $this->runCommand($command, ['--database' => 'foo']);
    }

    public function testStepMayBeSet()
    {
        $command = new SeedCommand($seeder = m::mock(Seeder::class));
        $app = new ApplicationDatabaseSeedStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $seeder->shouldReceive('paths')->once()->andReturn([]);
        $seeder->shouldReceive('setConnection')->once()->with(null);
        $seeder->shouldReceive('run')->once()->with([__DIR__.DIRECTORY_SEPARATOR.'seeds'], ['pretend' => false, 'step' => true]);
        $seeder->shouldReceive('getNotes')->andReturn([]);
        $seeder->shouldReceive('repositoryExists')->once()->andReturn(true);

        $this->runCommand($command, ['--step' => true]);
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new ArrayInput($input), new NullOutput);
    }
}

class ApplicationDatabaseSeedStub extends Application
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
