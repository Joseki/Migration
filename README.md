Joseki/Migration
================

[![Build Status](https://travis-ci.org/Joseki/Migration.svg?branch=master)](https://travis-ci.org/Joseki/Migration)
[![Latest Stable Version](https://poser.pugx.org/joseki/migration/v/stable)](https://packagist.org/packages/joseki/migration)

Requirements
------------

Joseki/Migration requires PHP 5.4 or higher.

- [Nette Framework](https://github.com/nette/nette)
- [Dibi](http://dibiphp.com/)
- [Symfony Console](https://github.com/symfony/Console)


Installation
------------

The best way to install Joseki/Migration is using  [Composer](http://getcomposer.org/):

```sh
$ composer require joseki/migration
```

Register compiler extension in your `config.neon`:

```yml
extensions:
  Migration: Joseki\Migration\DI\MigrationExtension
```

Example
-------

Add the following to your `config.neon`:

```yml
extensions:
  Migration: Joseki\Migration\DI\MigrationExtension

Migration:
  migrationDir:                     # specifies location of migrations
  migrationPrefix: Migration        # migration filename prefix
  migrationTable: _migration_log    # database table for migration sync
  logFile:                          # OPTIONAL
  options:                          # OPTIONAL
    collate: 'utf8_unicode_ci'      # OPTIONAL (DEFAULT VALUE)
```

Running a console command
-------------------------
Create a new empty migration

```sh
app/console joseki:migration:create
```

Sync all existing migrations with your database

```sh
app/console joseki:migration:migrate
```

Create a new migration based on existing LeanMapper entities (registered Repositories)

```sh
app/console joseki:migration:from-lm
```
