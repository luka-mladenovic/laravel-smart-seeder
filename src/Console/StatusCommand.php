<?php

namespace Lukam\SmartSeeder\Console;

use Illuminate\Support\Collection;
use Lukam\SmartSeeder\Seeds\Seeder;
use Symfony\Component\Console\Input\InputOption;

class StatusCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'seed:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show the status of each seed';

    /**
     * The seeder instance.
     *
     * @var \Lukam\Seeder\Seeds\advancedSeeder
     */
    protected $seeder;

    /**
     * Create a new seeder rollback command instance.
     *
     * @param \Lukam\Seeder\Seeds\seeder $seeder
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
        $this->seeder->setConnection($this->option('database'));

        if (! $this->seeder->repositoryExists()) {
            return $this->error('No seeds found.');
        }

        $ran = $this->seeder->getRepository()->getRan();

        $batches = $this->seeder->getRepository()->getSeedBatches();

        if (count($seeds = $this->getStatusFor($ran, $batches)) > 0) {
            $this->table(['Ran?', 'Seed', 'Batch'], $seeds);
        } else {
            $this->error('No seeds found');
        }
    }

    /**
     * Get the status for the given ran seeds.
     *
     * @param  array  $ran
     * @param  array  $batches
     * @return \Illuminate\Support\Collection
     */
    protected function getStatusFor(array $ran, array $batches)
    {
        return Collection::make($this->getAllSeedFiles())
                    ->map(function ($seed) use ($ran, $batches) {
                        $seedName = $this->seeder->getSeedName($seed);

                        return in_array($seedName, $ran)
                                ? ['<info>Y</info>', $seedName, $batches[$seedName]]
                                : ['<fg=yellow>N</fg=yellow>', $seedName];
                    });
    }

    /**
     * Get an array of all of the seed files.
     *
     * @return array
     */
    protected function getAllSeedFiles()
    {
        return $this->seeder->getSeedFiles($this->getSeedPaths());
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'],

            ['path', null, InputOption::VALUE_OPTIONAL, 'The path to the seed files to use.'],

            ['realpath', null, InputOption::VALUE_NONE, 'Indicate any provided seed file paths are pre-resolved absolute paths.'],
        ];
    }
}
