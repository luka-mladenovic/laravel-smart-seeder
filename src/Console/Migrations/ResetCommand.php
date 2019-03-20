<?php

namespace Lukam\SmartSeeder\Console\Migrations;

use Illuminate\Database\Console\Migrations\ResetCommand as MigrateResetCommand;

class ResetCommand extends MigrateResetCommand
{
    /**
     * Drop the seeds table when migration is reset.
     *
     * @return void
     */
    public function handle()
    {
        $database = $this->input->getOption('database');
        $pretend = $this->input->getOption('pretend');

        if(!$pretend) {
            $this->laravel['db']->connection($database)
                ->getSchemaBuilder()
                ->dropIfExists('seeds');
        }

        parent::handle();
    }
}
