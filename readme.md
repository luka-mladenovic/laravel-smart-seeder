# Smart seeder



## About

The goal of the package is to make handling of the seeder files easier by adding the commands to create, run, check status and revert the ran seed files.



## Configuration

By default the seeder uses `seeds` table to keep track of files.

To change the table, open the `config\database.php` file and add the `seeds` entry with the table name.

```php
'seeds' => 'seed_table_name',
```



## Commands

### Install

`php artisan seed:install`

This command will be execute the first time you use the `run` command.

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

Create a new database seeder file inside the `database\seeds` folder. 

To ensure that the seed files are run in the correct order the date timestamp is prefixed to the filename.



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



## Testing

```
phpunit
```



## License

The MIT License (MIT). See the license file for more information.