<?php

namespace Lukam\SmartSeeder\Console;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Input\InputOption;

class RefreshCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'seed:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset and re-run all seeds';

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

        // Next we'll gather some of the options so that we can have the right options
        // to pass to the commands. This includes options such as which database to
        // use and the path to use for the seed. Then we'll run the command.
        $database = $this->input->getOption('database');

        $path = $this->input->getOption('path');

        $force = $this->input->getOption('force');

        // If the "step" option is specified it means we only want to rollback a small
        // number of seeds before seed again. For example, the user might
        // only rollback and reseed the latest four seeds instead of all.
        $step = $this->input->getOption('step') ?: 0;

        if ($step > 0) {
            $this->runRollback($database, $path, $step, $force);
        } else {
            $this->runReset($database, $path, $force);
        }

        // The refresh command is essentially just a brief aggregate of a few other of
        // the seed commands and just provides a convenient wrapper to execute
        // them in succession. We'll also see if we need to re-seed the database.
        $this->call('seed:run', [
            '--database' => $database,
            '--path' => $path,
            '--realpath' => $this->input->getOption('realpath'),
            '--force' => $force,
        ]);
    }

    /**
     * Run the rollback command.
     *
     * @param  string  $database
     * @param  string  $path
     * @param  bool  $step
     * @param  bool  $force
     * @return void
     */
    protected function runRollback($database, $path, $step, $force)
    {
        $this->call('seed:rollback', [
            '--database' => $database,
            '--path' => $path,
            '--realpath' => $this->input->getOption('realpath'),
            '--step' => $step,
            '--force' => $force,
        ]);
    }

    /**
     * Run the reset command.
     *
     * @param  string  $database
     * @param  string  $path
     * @param  bool  $force
     * @return void
     */
    protected function runReset($database, $path, $force)
    {
        $this->call('seed:reset', [
            '--database' => $database,
            '--path' => $path,
            '--realpath' => $this->input->getOption('realpath'),
            '--force' => $force,
        ]);
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

            ['step', null, InputOption::VALUE_OPTIONAL, 'The number of seeds to be reverted & re-run.'],
        ];
    }
}
