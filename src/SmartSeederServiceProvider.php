<?php

namespace Lukam\SmartSeeder;

use Lukam\SmartSeeder\Seeds\Seeder;
use Lukam\SmartSeeder\Console\Seeds;
use Illuminate\Support\ServiceProvider;
use Lukam\SmartSeeder\Seeds\SeedCreator;
use Lukam\SmartSeeder\Console\Migrations;
use Lukam\SmartSeeder\Seeds\DatabaseSeedRepository;

class SmartSeederServiceProvider extends ServiceProvider
{
    protected $commands = [
        'SeedRun'       => 'command.seed.run',
        'SeedReset'     => 'command.seed.reset',
        'SeedStatus'    => 'command.seed.status',
        'SeedInstall'   => 'command.seed.install',
        'SeedRefresh'   => 'command.seed.refresh',
        'SeedRollback'  => 'command.seed.rollback',
        'MigrateReset'  => 'command.migrate.reset',
    ];

    protected $devCommands = [
        'SeedMake'      => 'command.seed.make',
    ];

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/database.php', 'database'
        );

        $this->registerRepository();

        $this->registerSeeder();

        $this->registerCreator();

        $this->registerCommands(array_merge(
            $this->commands, $this->devCommands
        ));
    }

    /**
     * Register the seeder repository service.
     *
     * @return void
     */
    protected function registerRepository()
    {
        $this->app->singleton('seed.repository', function ($app) {
            $table = $app['config']['database.seeds'];

            return new DatabaseSeedRepository($app['db'], $table);
        });
    }

    /**
     * Register the seeder service.
     *
     * @return void
     */
    protected function registerSeeder()
    {
        // The seeder is responsible for actually running and rollback the seeder
        // files in the application. We'll pass in our database connection resolver
        // so the seeder can resolve any of these connections when it needs to.
        $this->app->singleton('seeder', function ($app) {
            $repository = $app['seed.repository'];

            return new Seeder($repository, $app['db'], $app['files']);
        });
    }

    /**
     * Register the seed creator.
     *
     * @return void
     */
    protected function registerCreator()
    {
        $this->app->singleton('seed.creator', function ($app) {
            return new SeedCreator($app['files']);
        });
    }

    /**
     * Register the given commands.
     *
     * @param  array  $commands
     * @return void
     */
    protected function registerCommands($commands)
    {
        foreach (array_keys($commands) as $command) {
            call_user_func_array([$this, "register{$command}Command"], []);
        }
        $this->commands(array_values($commands));
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerSeedInstallCommand()
    {
        $this->app->singleton('command.seed.install', function ($app) {
            return new Seeds\InstallCommand($app['seed.repository']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerSeedMakeCommand()
    {
        $this->app->singleton('command.seed.make', function ($app) {
            return new Seeds\SeedMakeCommand($app['seed.creator'], $app['composer']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerSeedStatusCommand()
    {
        $this->app->singleton('command.seed.status', function ($app) {
            return new Seeds\StatusCommand($app['seeder']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerSeedRunCommand()
    {
        $this->app->singleton('command.seed.run', function ($app) {
            return new Seeds\SeedCommand($app['seeder']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerSeedRollbackCommand()
    {
        $this->app->singleton('command.seed.rollback', function ($app) {
            return new Seeds\RollbackCommand($app['seeder']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerSeedResetCommand()
    {
        $this->app->singleton('command.seed.reset', function ($app) {
            return new Seeds\ResetCommand($app['seeder']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerSeedRefreshCommand()
    {
        $this->app->singleton('command.seed.refresh', function ($app) {
            return new Seeds\RefreshCommand($app['seeder']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateResetCommand()
    {
        $this->app->singleton('command.migrate.reset', function ($app) {
            return new Migrations\ResetCommand($app['migrator']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array_merge([
            'seeder', 'seed.repository', 'seed.creator',
        ],array_values($this->commands), array_values($this->devCommands));
    }
}
