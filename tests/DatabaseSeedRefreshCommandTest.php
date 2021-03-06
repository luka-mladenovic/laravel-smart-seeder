<?php
namespace Tests;

use Mockery as m;
use Lukam\SmartSeeder\Seeds\Seeder;
use Illuminate\Foundation\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Lukam\SmartSeeder\Console\Seeds\SeedCommand;
use Lukam\SmartSeeder\Console\Seeds\RefreshCommand;
use Lukam\SmartSeeder\Console\Seeds\RollbackCommand;
use Symfony\Component\Console\Application as ConsoleApplication;

class DatabaseSeedRefreshCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testRefreshCommandCallsCommandsWithProperArguments()
    {
        $command = new RefreshCommand($seeder = m::mock(Seeder::class));

        $app = new ApplicationDatabaseRefreshStub(['path.database' => __DIR__, 'env' => 'development']);
        $console = m::mock(ConsoleApplication::class)->makePartial();
        $console->__construct();
        $command->setLaravel($app);
        $command->setApplication($console);

        $resetCommand = m::mock(ResetCommand::class);
        $seedCommand = m::mock(SeedCommand::class);

        $console->shouldReceive('find')->with('seed:reset')->andReturn($resetCommand);
        $console->shouldReceive('find')->with('seed:run')->andReturn($seedCommand);

        $quote = DIRECTORY_SEPARATOR == '\\' ? '"' : "'";
        $resetCommand->shouldReceive('run')->with(new InputMatcher("--database --path --realpath --force {$quote}seed:reset{$quote}"), m::any());
        $seedCommand->shouldReceive('run')->with(new InputMatcher("--database --path --realpath --force {$quote}seed:run{$quote}"), m::any());

        $this->runCommand($command);
    }

    public function testRefreshCommandCallsCommandsWithStep()
    {
        $command = new RefreshCommand($seeder = m::mock(Seeder::class));

        $app = new ApplicationDatabaseRefreshStub(['path.database' => __DIR__, 'env' => 'development']);
        $console = m::mock(ConsoleApplication::class)->makePartial();
        $console->__construct();
        $command->setLaravel($app);
        $command->setApplication($console);

        $rollbackCommand = m::mock(RollbackCommand::class);
        $seedCommand = m::mock(SeedCommand::class);

        $console->shouldReceive('find')->with('seed:rollback')->andReturn($rollbackCommand);
        $console->shouldReceive('find')->with('seed:run')->andReturn($seedCommand);

        $quote = DIRECTORY_SEPARATOR == '\\' ? '"' : "'";

        $rollbackCommand->shouldReceive('run')->with(new InputMatcher("--database --path --realpath --step=2 --force {$quote}seed:rollback{$quote}"), m::any());
        $seedCommand->shouldReceive('run')->with(new InputMatcher("--database --path --realpath --force {$quote}seed:run{$quote}"), m::any());

        $this->runCommand($command, ['--step' => 2]);
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new ArrayInput($input), new NullOutput);
    }
}

class InputMatcher extends m\Matcher\MatcherAbstract
{
    /**
     * @param  \Symfony\Component\Console\Input\ArrayInput  $actual
     * @return bool
     */
    public function match(&$actual)
    {
        return (string) $actual == $this->_expected;
    }
    public function __toString()
    {
        return '';
    }
}

class ApplicationDatabaseRefreshStub extends Application
{
    public function __construct(array $data = [])
    {
        foreach ($data as $abstract => $instance) {
            $this->instance($abstract, $instance);
        }
    }
}
