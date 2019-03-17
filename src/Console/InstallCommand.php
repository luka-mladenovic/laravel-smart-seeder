<?php

namespace Lukam\SmartSeeder\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Lukam\SmartSeeder\Seeds\SeedRepositoryInterface;

class InstallCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'seed:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the seeder repository';

    /**
     * The repository instance.
     *
     * @var \Illuminate\Database\AdvancedSeeds\AdvancedSeedRepository
     */
    protected $repository;

    /**
     * Create a new seed install command instance.
     *
     * @param  \Illuminate\Database\AdvancedSeeds\AdvancedSeedRepository  $repository
     * @return void
     */
    public function __construct(SeedRepositoryInterface $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->repository->setSource($this->input->getOption('database'));

        if($this->repository->repositoryExists()) {
        	$this->info('Seed table already exists.');
        	return;
        }

        $this->repository->createRepository();

        $this->info('Seed table created successfully.');
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
        ];
    }
}
