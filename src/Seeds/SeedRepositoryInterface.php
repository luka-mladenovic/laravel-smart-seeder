<?php

namespace Lukam\SmartSeeder\Seeds;

interface SeedRepositoryInterface
{
    /**
     * Get the completed seeds.
     *
     * @return array
     */
    public function getRan();

    /**
     * Get list of seeds.
     *
     * @param  int  $steps
     * @return array
     */
    public function getSeeds($steps);

    /**
     * Get the last seed batch.
     *
     * @return array
     */
    public function getLast();

    /**
     * Get the completed seeds with their batch numbers.
     *
     * @return array
     */
    public function getSeedBatches();

    /**
     * Log that a seed was run.
     *
     * @param  string  $file
     * @param  int  $batch
     * @return void
     */
    public function log($file, $batch);

    /**
     * Remove a seed from the log.
     *
     * @param  object  $seed
     * @return void
     */
    public function delete($seed);

    /**
     * Get the next seed batch number.
     *
     * @return int
     */
    public function getNextBatchNumber();

    /**
     * Create the seed repository data store.
     *
     * @return void
     */
    public function createRepository();

    /**
     * Determine if the seed repository exists.
     *
     * @return bool
     */
    public function repositoryExists();

    /**
     * Set the information source to gather data.
     *
     * @param  string  $name
     * @return void
     */
    public function setSource($name);
}
