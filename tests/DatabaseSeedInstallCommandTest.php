<?php
namespace Tests;

use Mockery as m;
use Illuminate\Foundation\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Lukam\SmartSeeder\Console\Seeds\InstallCommand;
use Lukam\SmartSeeder\Seeds\SeedRepositoryInterface;

class DatabaseSeedInstallCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testFireCallsRepositoryToInstall()
    {
        $command = new InstallCommand($repo = m::mock(SeedRepositoryInterface::class));
        $command->setLaravel(new Application);
        $repo->shouldReceive('setSource')->once()->with('foo');
        $repo->shouldReceive('repositoryExists')->once()->andReturn(false);
        $repo->shouldReceive('createRepository')->once();

        $this->runCommand($command, ['--database' => 'foo']);
    }

    protected function runCommand($command, $options = [])
    {
        return $command->run(new ArrayInput($options), new NullOutput);
    }
}
