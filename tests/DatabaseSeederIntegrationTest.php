<?php

use Mockery as m;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;
use Illuminate\Console\OutputStyle;
use Illuminate\Container\Container;
use Lukam\SmartSeeder\Seeds\Seeder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Database\Capsule\Manager as DB;
use Lukam\SmartSeeder\Seeds\DatabaseSeedRepository;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;

class DatabaseSeederIntegrationTest extends TestCase
{
    protected $db;
    protected $seeder;
    protected $migrator;

    /**
     * Bootstrap Eloquent.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->db = $db = new DB;

        $db->addConnection([
            'driver'    => 'sqlite',
            'database'  => ':memory:',
        ]);

        $db->setAsGlobal();

        $container = new Container;
        $container->instance('db', $db->getDatabaseManager());

        Facade::setFacadeApplication($container);

        $this->migrator = new Migrator(
            $repository = new DatabaseMigrationRepository($db->getDatabaseManager(), 'migrations'),
            $db->getDatabaseManager(),
            new Filesystem
        );

        if (! $repository->repositoryExists()) {
            $repository->createRepository();
        }

        $this->migrator->run([__DIR__.'/migrations']);

        $this->seeder = new Seeder(
            $repository = new DatabaseSeedRepository($db->getDatabaseManager(), 'seeds'),
            $db->getDatabaseManager(),
            new Filesystem
        );

        if (! $repository->repositoryExists()) {
            $repository->createRepository();
        }
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
    }

    public function testBasicSeedOfSingleFolder()
    {
        $this->seeder->run([__DIR__.'/seeds/one']);

        $this->assertEquals(1, $this->db->table('users')->count());
    }

    public function testSeedsCanBeRolledBack()
    {
        $this->seeder->run([__DIR__.'/seeds/one']);

        $rolledBack = $this->seeder->rollback([__DIR__.'/seeds/one']);
        $this->assertEquals(0, $this->db->table('users')->count());

        $this->assertTrue(Str::contains($rolledBack[0], 'create_users'));
    }

    public function testSeedsCanBeReset()
    {
        $this->seeder->run([__DIR__.'/seeds/one']);

        $rolledBack = $this->seeder->reset([__DIR__.'/seeds/one']);
        $this->assertEquals(0, $this->db->table('users')->count());

        $this->assertTrue(Str::contains($rolledBack[0], 'create_users'));
    }

    public function testNoErrorIsThrownWhenNoOutstandingSeedsExist()
    {
        $this->seeder->run([__DIR__.'/seeds/one']);
        $this->seeder->run([__DIR__.'/seeds/one']);
    }

    public function testNoErrorIsThrownWhenNothingToRollback()
    {


        $this->seeder->run([__DIR__.'/seeds/one']);

        $this->seeder->rollback([__DIR__.'/seeds/one']);
        $this->seeder->rollback([__DIR__.'/seeds/one']);
    }

    public function testSeedsCanRunAcrossMultiplePaths()
    {
        $this->seeder->run([__DIR__.'/seeds/one', __DIR__.'/seeds/two']);

        $this->assertEquals(1, $this->db->table('users')->count());
        $this->assertEquals(1, $this->db->table('flights')->count());
    }

    public function testSeedsCanBeRolledBackAcrossMultiplePaths()
    {
        $this->seeder->run([__DIR__.'/seeds/one', __DIR__.'/seeds/two']);

        $this->seeder->rollback([__DIR__.'/seeds/one', __DIR__.'/seeds/two']);

        $this->assertEquals(0, $this->db->table('users')->count());
        $this->assertEquals(0, $this->db->table('flights')->count());
    }

    public function testSeedsCanBeResetAcrossMultiplePaths()
    {
        $this->seeder->run([__DIR__.'/seeds/one', __DIR__.'/seeds/two']);

        $this->seeder->reset([__DIR__.'/seeds/one', __DIR__.'/seeds/two']);

        $this->assertEquals(0, $this->db->table('users')->count());
        $this->assertEquals(0, $this->db->table('flights')->count());
    }
}
