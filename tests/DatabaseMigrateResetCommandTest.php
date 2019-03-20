<?php
namespace Tests;

use StdClass;
use Mockery as m;
use Lukam\SmartSeeder\Seeds\Seeder;
use Illuminate\Foundation\Application;
use Illuminate\Database\Migrations\Migrator;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Application as ConsoleApplication;
use Lukam\SmartSeeder\Console\Migrations\ResetCommand as MigrateResetCommand;

class DatabaseMigrateResetCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testResetCommandDropsSeedsTable()
    {
        $command = new MigrateResetCommand($migrator = $this->mockMigrator());

        // Mock DatabaseManager, Builder and
        // Connection on a basic object.
        $db = m::mock(StdClass::class);
        $db->shouldReceive('dropIfExists')->with('seeds');
        $db->shouldReceive('getSchemaBuilder')->andReturn($db);
        $db->shouldReceive('connection')->with('foo')->andReturn($db);

        $app = new ApplicationDatabaseMigrationResetStub(['path.database' => __DIR__, 'db' => $db]);
        $app->useDatabasePath(__DIR__);

        $command->setLaravel($app);

        $this->runCommand($command, ['--database' => 'foo']);
    }

    protected function mockMigrator()
    {
        $migrator = m::mock(Migrator::class);

        $migrator->shouldReceive('paths')->once()->andReturn([]);
        $migrator->shouldReceive('setConnection')->once()->with('foo');
        $migrator->shouldReceive('repositoryExists')->once()->andReturn(true);
        $migrator->shouldReceive('reset')->once()->with([__DIR__.DIRECTORY_SEPARATOR.'migrations'], false);
        $migrator->shouldReceive('getNotes')->andReturn([]);

        return $migrator;
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new ArrayInput($input), new NullOutput);
    }
}

class ApplicationDatabaseMigrationResetStub extends Application
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
