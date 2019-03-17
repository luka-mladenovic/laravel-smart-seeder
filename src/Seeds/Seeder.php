<?php

namespace Lukam\SmartSeeder\Seeds;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\ConnectionResolverInterface as Resolver;

class Seeder
{
    /**
     * The seed repository implementation.
     *
     * @var \Lukam\SmartSeeder\Seeds\SeedRepositoryInterface
     */
    protected $repository;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The connection resolver instance.
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * The name of the default connection.
     *
     * @var string
     */
    protected $connection;

    /**
     * The notes for the current operation.
     *
     * @var array
     */
    protected $notes = [];

    /**
     * The paths to all of the seed files.
     *
     * @var array
     */
    protected $paths = [];

    /**
     * Create a new seed instance.
     *
     * @param  \Lukam\SmartSeeder\Seeds\SeedRepositoryInterface  $repository
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(SeedRepositoryInterface $repository,
                                Resolver $resolver,
                                Filesystem $files)
    {
        $this->files = $files;
        $this->resolver = $resolver;
        $this->repository = $repository;
    }

    /**
     * Run the pending seeds at a given path.
     *
     * @param  array|string  $paths
     * @param  array  $options
     * @return array
     */
    public function run($paths = [], array $options = [])
    {
        $this->notes = [];

        // Once we grab all of the seed files for the path, we will compare them
        // against the seeds that have already been run for this package then
        // run each of the outstanding seeds against a database connection.
        $files = $this->getSeedFiles($paths);

        $this->requireFiles($seeds = $this->pendingSeeds(
            $files, $this->repository->getRan()
        ));

        // Once we have all these seeds that are outstanding we are ready to run
        // we will go ahead and run them "up". This will execute each seed as
        // an operation against a database. Then we'll return this list of them.
        $this->runPending($seeds, $options);

        return $seeds;
    }

    /**
     * Get the seed files that have not yet run.
     *
     * @param  array  $files
     * @param  array  $ran
     * @return array
     */
    protected function pendingSeeds($files, $ran)
    {
        return Collection::make($files)
                ->reject(function ($file) use ($ran) {
                    return in_array($this->getSeedName($file), $ran);
                })->values()->all();
    }

    /**
     * Run an array of seeds.
     *
     * @param  array  $seeds
     * @param  array  $options
     * @return void
     */
    public function runPending(array $seeds, array $options = [])
    {
        // First we will just make sure that there are any seeds to run. If there
        // aren't, we will just make a note of it to the developer so they're aware
        // that all of the seeds have been run against this database system.
        if (count($seeds) === 0) {
            $this->note('<info>Nothing to seed.</info>');

            return;
        }

        // Next, we will get the next batch number for the seeds so we can insert
        // correct batch number in the database seeds repository when we store
        // each seed's execution. We will also extract a few of the options.
        $batch = $this->repository->getNextBatchNumber();

        $pretend = $options['pretend'] ?? false;

        $step = $options['step'] ?? false;

        // Once we have the array of seeds, we will spin through them and run the
        // seeds "up" so the changes are made to the databases. We'll then log
        // that the seed was run so we don't repeat it next time we execute.
        foreach ($seeds as $file) {
            $this->runUp($file, $batch, $pretend);

            if ($step) {
                $batch++;
            }
        }
    }

    /**
     * Run "run" a seed instance.
     *
     * @param  string  $file
     * @param  int     $batch
     * @param  bool    $pretend
     * @return void
     */
    protected function runUp($file, $batch, $pretend)
    {
        // First we will resolve a "real" instance of the seed class from this
        // seed file name. Once we have the instances we can run the actual
        // command such as "run" or "down", or we can just simulate the action.
        $seed = $this->resolve(
            $name = $this->getSeedName($file)
        );

        if ($pretend) {
            return $this->pretendToRun($seed, 'run');
        }

        $this->note("<comment>Seeding:</comment> {$name}");

        $this->runSeed($seed, 'run');

        // Once we have run a seeds class, we will log that it was run in this
        // repository so that we don't try to run it next time we do a seed
        // in the application. A seed repository keeps the seed order.
        $this->repository->log($name, $batch);

        $this->note("<info>Seeded:</info>  {$name}");
    }

    /**
     * Rollback the last seed operation.
     *
     * @param  array|string $paths
     * @param  array  $options
     * @return array
     */
    public function rollback($paths = [], array $options = [])
    {
        $this->notes = [];

        // We want to pull in the last batch of seeds that ran on the previous
        // seed operation. We'll then reverse those seeds and run each
        // of them "down" to reverse the last seed "operation" which ran.
        $seeds = $this->getSeedsForRollback($options);

        if (count($seeds) === 0) {
            $this->note('<info>Nothing to rollback.</info>');

            return [];
        }

        return $this->rollbackSeeds($seeds, $paths, $options);
    }

    /**
     * Get the seeds for a rollback operation.
     *
     * @param  array  $options
     * @return array
     */
    protected function getSeedsForRollback(array $options)
    {
        if (($steps = $options['step'] ?? 0) > 0) {
            return $this->repository->getSeeds($steps);
        } else {
            return $this->repository->getLast();
        }
    }

    /**
     * Rollback the given seeds.
     *
     * @param  array  $seeds
     * @param  array|string  $paths
     * @param  array  $options
     * @return array
     */
    protected function rollbackSeeds(array $seeds, $paths, array $options)
    {
        $rolledBack = [];

        $this->requireFiles($files = $this->getSeedFiles($paths));

        // Next we will run through all of the seeds and call the "down" method
        // which will reverse each seed in order. This getLast method on the
        // repository already returns these seed's names in reverse order.
        foreach ($seeds as $seed) {
            $seed = (object) $seed;

            if (! $file = Arr::get($files, $seed->seed)) {
                $this->note("<fg=red>Seed not found:</> {$seed->seed}");

                continue;
            }

            $rolledBack[] = $file;

            $this->runDown(
                $file, $seed,
                $options['pretend'] ?? false
            );
        }

        return $rolledBack;
    }

    /**
     * Rolls all of the currently applied seeds back.
     *
     * @param  array|string $paths
     * @param  bool  $pretend
     * @return array
     */
    public function reset($paths = [], $pretend = false)
    {
        $this->notes = [];

        // Next, we will reverse the seed list so we can run them back in the
        // correct order for resetting this database. This will allow us to get
        // the database back into its "empty" state ready for the seeds.
        $seeds = array_reverse($this->repository->getRan());

        if (count($seeds) === 0) {
            $this->note('<info>Nothing to rollback.</info>');

            return [];
        }

        return $this->resetSeeds($seeds, $paths, $pretend);
    }

    /**
     * Reset the given seeds.
     *
     * @param  array  $seeds
     * @param  array  $paths
     * @param  bool  $pretend
     * @return array
     */
    protected function resetSeeds(array $seeds, array $paths, $pretend = false)
    {
        // Since the getRan method that retrieves the seed name just gives us the
        // seed name, we will format the names into objects with the name as a
        // property on the objects so that we can pass it to the rollback method.
        $seeds = collect($seeds)->map(function ($m) {
            return (object) ['seed' => $m];
        })->all();

        return $this->rollbackSeeds(
            $seeds, $paths, compact('pretend')
        );
    }

    /**
     * Run "revert" on seed instance.
     *
     * @param  string  $file
     * @param  object  $seed
     * @param  bool    $pretend
     * @return void
     */
    protected function runDown($file, $seed, $pretend)
    {
        // First we will get the file name of the seed so we can resolve out an
        // instance of the seed. Once we get an instance we can either run a
        // pretend execution of the seed or we can run the real seed.
        $instance = $this->resolve(
            $name = $this->getSeedName($file)
        );

        $this->note("<comment>Rolling back:</comment> {$name}");

        if ($pretend) {
            return $this->pretendToRun($instance, 'revert');
        }

        $this->runSeed($instance, 'revert');

        // Once we have successfully run the seed "down" we will remove it from
        // the seed repository so it will be considered to have not been run
        // by the application then will be able to fire by any later operation.
        $this->repository->delete($seed);

        $this->note("<info>Rolled back:</info>  {$name}");
    }

    /**
     * Run a seed inside a transaction if the database supports it.
     *
     * @param  object  $seed
     * @param  string  $method
     * @return void
     */
    protected function runSeed($seed, $method)
    {
        $connection = $this->resolveConnection(
            $this->getConnection()
        );

        $callback = function () use ($seed, $method) {
            if (method_exists($seed, $method)) {
                Model::unguarded(function () use ($seed, $method) {
                    $seed->{$method}();
                });
            }
        };

        $this->getSchemaGrammar($connection)->supportsSchemaTransactions()
            && $seed->withinTransaction
                    ? $connection->transaction($callback)
                    : $callback();
    }

    /**
     * Pretend to run the seeds.
     *
     * @param  object  $seed
     * @param  string  $method
     * @return void
     */
    protected function pretendToRun($seed, $method)
    {
        foreach ($this->getQueries($seed, $method) as $query) {
            $name = get_class($seed);

            $this->note("<info>{$name}:</info> {$query['query']}");
        }
    }

    /**
     * Get all of the queries that would be run for a seed.
     *
     * @param  object  $seed
     * @param  string  $method
     * @return array
     */
    protected function getQueries($seed, $method)
    {
        // Now that we have the connections we can resolve it and pretend to run the
        // queries against the database returning the array of raw SQL statements
        // that would get fired against the database system for this seed.
        $db = $this->resolveConnection(
            $this->getConnection()
        );

        return $db->pretend(function () use ($seed, $method) {
            if (method_exists($seed, $method)) {
                $seed->{$method}();
            }
        });
    }

    /**
     * Resolve a seed instance from a file.
     *
     * @param  string  $file
     * @return object
     */
    public function resolve($file)
    {
        $class = Str::studly($file);

        return new $class;
    }

    /**
     * Get all of the seed files in a given path.
     *
     * @param  string|array  $paths
     * @return array
     */
    public function getSeedFiles($paths)
    {
        return Collection::make($paths)->flatMap(function ($path) {
            return $this->files->glob($path.'/*.php');
        })->filter()->sortBy(function ($file) {
            return $this->getSeedName($file);
        })->values()->keyBy(function ($file) {
            return $this->getSeedName($file);
        })->all();
    }

    /**
     * Require in all the seed files in a given path.
     *
     * @param  array   $files
     * @return void
     */
    public function requireFiles(array $files)
    {
        foreach ($files as $file) {
            $this->files->requireOnce($file);
        }
    }

    /**
     * Get the name of the seed.
     *
     * @param  string  $path
     * @return string
     */
    public function getSeedName($path)
    {
        return str_replace('.php', '', basename($path));
    }

    /**
     * Register a custom seed path.
     *
     * @param  string  $path
     * @return void
     */
    public function path($path)
    {
        $this->paths = array_unique(array_merge($this->paths, [$path]));
    }

    /**
     * Get all of the custom seed paths.
     *
     * @return array
     */
    public function paths()
    {
        return $this->paths;
    }

    /**
     * Get the default connection name.
     *
     * @return string
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Set the default connection name.
     *
     * @param  string  $name
     * @return void
     */
    public function setConnection($name)
    {
        if (! is_null($name)) {
            $this->resolver->setDefaultConnection($name);
        }

        $this->repository->setSource($name);

        $this->connection = $name;
    }

    /**
     * Resolve the database connection instance.
     *
     * @param  string  $connection
     * @return \Illuminate\Database\Connection
     */
    public function resolveConnection($connection)
    {
        return $this->resolver->connection($connection ?: $this->connection);
    }

    /**
     * Get the schema grammar out of a seed connection.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @return \Illuminate\Database\Schema\Grammars\Grammar
     */
    protected function getSchemaGrammar($connection)
    {
        if (is_null($grammar = $connection->getSchemaGrammar())) {
            $connection->useDefaultSchemaGrammar();

            $grammar = $connection->getSchemaGrammar();
        }

        return $grammar;
    }

    /**
     * Get the seed repository instance.
     *
     * @return \Lukam\SmartSeeder\Seeds\SeedRepositoryInterface
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Determine if the seed repository exists.
     *
     * @return bool
     */
    public function repositoryExists()
    {
        return $this->repository->repositoryExists();
    }

    /**
     * Get the file system instance.
     *
     * @return \Illuminate\Filesystem\Filesystem
     */
    public function getFilesystem()
    {
        return $this->files;
    }

    /**
     * Raise a note event for the seed.
     *
     * @param  string  $message
     * @return void
     */
    protected function note($message)
    {
        $this->notes[] = $message;
    }

    /**
     * Get the notes for the last operation.
     *
     * @return array
     */
    public function getNotes()
    {
        return $this->notes;
    }
}
