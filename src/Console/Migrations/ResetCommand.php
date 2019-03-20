<?php

namespace Lukam\SmartSeeder\Console\Migrations;

use Illuminate\Database\Console\Migrations\ResetCommand as MigrateResetCommand;

class ResetCommand extends MigrateResetCommand
{
    /**
     * When the migration is reset drop the seeds table.
     *
     * @return void
     */
    public function handle()
    {
        parent::handle();

        $database = $this->input->getOption('database');

        $this->laravel['db']->connection($database)
                ->getSchemaBuilder()
                ->dropIfExists('seeds');
    }
}
