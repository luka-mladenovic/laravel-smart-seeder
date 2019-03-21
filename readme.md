# Smart seeder



## About

The goal of the package is to make handling of the seeder files easier by adding the commands to create, run, check status and revert the ran seed files. 

This is achieved by reusing the existing Migration package and adapting it to work with seed files.



## Requirements

The package works with Laravel version 5.6+.



## Compatibility with existing seed files

The existing seed files are not yet compatible with this package. 

- When the package [seed file is created](#make) using the  `php artisan seed:make` command the filename is prefixed with a date timestamp to ensure that the seed files are ran in a correct order. 
- Refreshing the migrations with the `--seed` parameter will still call the `db:seed` command to seed the files which is not a part of this package.



## Installation

Install the package using Composer:

```
composer require lukam/smart-seeder
```



## Configuration

By default the seeder uses `seeds` table to keep track of files.

To change the table, open the `config\database.php` file and add the `seeds` entry with the table name.

```php
'seeds' => 'seed_table_name',
```



## Commands

### Install

`php artisan seed:install`

This command will be executed the first time you use the `run` command.

This will create the seeder repository and database table. The table keeps track of which seed files have already been run. 



### Status

`php artisan seed:status`

Show status of each seed file.

```
+------+-----------------------------+-------+
| Ran? | Seed                        | Batch |
+------+-----------------------------+-------+
| Y    | 2019_03_18_155332_users     | 1     |
| N    | 2019_03_19_184525_documents |       |
+------+-----------------------------+-------+
```



### Make

`php artisan seed:make filename`

Creates a new database seeder file inside the `database\seeds` folder. 

To ensure that the seed files are run in the correct order a timestamp is prefixed to the filename.

The seed file now also contains the `revert` method which enables you to delete data from a table when rolling back the seeds. For more information see the [revert method](#revert-method) section.



### Run

`php artisan seed:run`

Run the database seeders.



### Rollback

`php artisan seed:rollback`

Rollback last run seeder files.



### Reset

`php artisan seed:reset`

Rollback all seeder files.



### Refresh

`php artisan seed:refresh`

Rollback and re-run all seeder files.



## Revert method

In some cases you may want to delete or update the table data when a seed is rolled back. This can be done using the `revert` method. The method should contain the query that will be executed when the seed file is rolled back.

```php
<?php

use App\User;
use Illuminate\Database\Seeder;

class SeedUsers extends Seeder
{
    /**
     * Run the seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create a new user
        User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('secret')
        ]);
    }

    /**
     * Revert the seeds.
     *
     * @return void
     */
    public function revert()
    {
        // Delete the user
        User::whereEmail('john@example.com')->delete();
    }
}
```



## Testing

```
phpunit
```



## License

The MIT License (MIT). See the license file for more information.