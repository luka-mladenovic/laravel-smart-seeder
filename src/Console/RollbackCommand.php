<?php

namespace Lukam\SmartSeeder\Console;

use Lukam\SmartSeeder\Seeds\Seeder;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Input\InputOption;

class RollbackCommand extends BaseCommand
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'seed:rollback';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback the last database seed';

    /**
     * The seeder instance.
     *
     * @var \Illuminate\Database\Seeds\Seeder
     */
    protected $seeder;

    /**
     * Create a new seed rollback command instance.
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

        $this->seeder->setConnection($this->option('database'));

        $this->seeder->rollback(
            $this->getSeedPaths(), [
                'pretend' => $this->option('pretend'),
                'step' => (int) $this->option('step'),
            ]
        );

        // Once the seeder has run we will grab the note output and send it out to
        // the console screen, since the seeder itself functions without having
        // any instances of the OutputInterface contract passed into the class.
        foreach ($this->seeder->getNotes() as $note) {
            $this->output->writeln($note);
        }
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

            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],

            ['path', null, InputOption::VALUE_OPTIONAL, 'The path to the seeds files to be executed.'],

            ['realpath', null, InputOption::VALUE_NONE, 'Indicate any provided seed file paths are pre-resolved absolute paths.'],

            ['pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run.'],

            ['step', null, InputOption::VALUE_OPTIONAL, 'The number of seeds to be reverted.'],
        ];
    }
}
