<?php

namespace Lukam\SmartSeeder\Console;

use Illuminate\Support\Str;
use Illuminate\Support\Composer;
use Lukam\SmartSeeder\Seeds\SeedCreator;

class SeedMakeCommand extends BaseCommand
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'seed:make {name : The name of the seed.}
        {--path= : The location where the seed file should be created.}
        {--realpath : Indicate any provided seed file paths are pre-resolved absolute paths.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new seed file';

    /**
     * The seed creator instance.
     *
     * @var \Lukam\SmartSeeder\Seeds\SeedCreator
     */
    protected $creator;

    /**
     * The Composer instance.
     *
     * @var \Illuminate\Support\Composer
     */
    protected $composer;

    /**
     * Create a new seed install command instance.
     *
     * @param  \Lukam\SmartSeeder\Seeds\SeedCreator  $creator
     * @param  \Illuminate\Support\Composer  $composer
     * @return void
     */
    public function __construct(SeedCreator $creator, Composer $composer)
    {
        parent::__construct();

        $this->creator = $creator;
        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // It's possible for the developer to specify the tables to modify in this
        // schema operation. The developer may also specify if this table needs
        // to be freshly created so we can create the appropriate seed.
        $name = Str::snake(trim($this->input->getArgument('name')));

        // Now we are ready to write the seed out to disk. Once we've written
        // the seed out, we will dump-autoload for the entire framework to
        // make sure that the seed are registered by the class loaders.
        $this->writeSeed($name);

        $this->composer->dumpAutoloads();
    }

    /**
     * Write the seed file to disk.
     *
     * @param  string  $name
     * @param  string  $table
     * @param  bool    $create
     * @return string
     */
    protected function writeSeed($name)
    {
        $file = pathinfo($this->creator->create(
            $name, $this->getSeedPath()
        ), PATHINFO_FILENAME);

        $this->line("<info>Created Seed:</info> {$file}");
    }

    /**
     * Get seed path (either specified by '--path' option or default location).
     *
     * @return string
     */
    protected function getSeedPath()
    {
        if (! is_null($targetPath = $this->input->getOption('path'))) {
            return ! $this->usingRealPath()
                            ? $this->laravel->basePath().'/'.$targetPath
                            : $targetPath;
        }

        return parent::getSeedPath();
    }

    /**
     * Determine if the given path(s) are pre-resolved "real" paths.
     *
     * @return bool
     */
    protected function usingRealPath()
    {
        return $this->input->hasOption('realpath') && $this->option('realpath');
    }
}
