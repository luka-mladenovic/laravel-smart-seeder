<?php

namespace Lukam\SmartSeeder\Console;

use Lukam\SmartSeeder\Seeds\Seeder;
use Illuminate\Console\ConfirmableTrait;

class SeedCommand extends BaseCommand
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:run {--database= : The database connection to use.}
                {--force : Force the operation to run when in production.}
                {--path= : The path to the seed files to be executed.}
                {--realpath : Indicate any provided seed file paths are pre-resolved absolute paths.}
                {--pretend : Dump the SQL queries that would be run.}
                {--step : Force the seeds to be run so they can be rolled back individually.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the database seeds';

    /**
     * The seeder instance.
     *
     * @var \Lukam\SmartSeeder\Seeds\Seeder
     */
    protected $seeder;

    /**
     * Create a new seed command instance.
     *
     * @param  \Lukam\SmartSeeder\Seeds\Seeder  $seeder
     * @return void
     */
    public function __construct(Seeder $seeder)
    {
        parent::__construct();

        $this->seeder = $seeder;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        $this->prepareDatabase();

        // Next, we will check to see if a path option has been defined. If it has
        // we will use the path relative to the root of this installation folder
        // so that seeds may be run for any path within the applications.
        $this->seeder->run($this->getSeedPaths(), [
            'pretend' => $this->option('pretend'),
            'step' => $this->option('step'),
        ]);

        // Once the seeder has run we will grab the note output and send it out to
        // the console screen, since the seeder itself functions without having
        // any instances of the OutputInterface contract passed into the class.
        foreach ($this->seeder->getNotes() as $note) {
            $this->output->writeln($note);
        }
    }

    /**
     * Prepare the seed database for running.
     *
     * @return void
     */
    protected function prepareDatabase()
    {
        $this->seeder->setConnection($this->option('database'));

        if (! $this->seeder->repositoryExists()) {
            $this->call(
                'seed:install', ['--database' => $this->option('database')]
            );
        }
    }
}
