# Environmentalist [![Packagist](https://img.shields.io/packagist/v/DFurnes/environmentalist.svg?style=flat)](https://packagist.org/packages/DFurnes/environmentalist)

**Dead-simple setup for Laravel apps.** Environmentalist is a collection of handy tools for making setup scripts with [Artisan](https://laravel.com/docs/5.5/artisan). Easily create an environment file, prompt the user for values (with smart defaults & autocomplete), and run commands - all without needing to write out or follow lengthy directions.

### Installation

To install this package simply add it to your Laravel project using composer:

```shell
$ composer require dfurnes/environmentalist
```

### Usage

Environmentalist is essentially a [PHP trait](https://www.php.net/manual/en/language.oop5.traits.php) that provides a collection of helpful methods that you can use within your console commands for setting up a project.

To begin using it, you need to create a console command in your Laravel project. As an exmaple, let's create a new `SetupCommand` for our project:

```shell
$ php artisan make:command SetupCommand
```

In the `app/Console/Commands` directory, you should have a new `SetupCommand.php` file.

Now

Now, within this file, you can `use` the `ConfiguresApplication` trait in the class and import the file:

```php
namespace App\Console\Commands;

use DFurnes\Environmentalist\ConfiguresApplication;
use Illuminate\Console\Command;

class SetupCommand extends Command
{
    use ConfiguresApplication;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configure your application.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // Setup code and environmentalist configuration methods used here!
    }

}
```

Be sure to set a `$signature` to define the name of the command to run, and a `$description` that describes what it does.

### License

MIT &copy; [David Furnes](https://dfurnes.com)
