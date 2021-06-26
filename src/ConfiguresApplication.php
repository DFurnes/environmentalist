<?php

namespace DFurnes\Environmentalist;

use Closure;
use Dotenv\Dotenv;
use Dotenv\Repository\Adapter\EnvConstAdapter;
use Dotenv\Repository\Adapter\PutenvAdapter;
use Dotenv\Repository\Adapter\ServerConstAdapter;
use Dotenv\Repository\RepositoryBuilder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Symfony\Component\Console\Output\NullOutput;

/** @mixin \Illuminate\Console\Command */
trait ConfiguresApplication
{
    /**
     * Create the '.env' from '.env.example'.
     */
    public function createEnvironmentFile()
    {
        $path = app()->environmentFilePath();

        $shouldCreateFile = ! file_exists($path) || $this->option('reset');

        if ($shouldCreateFile) {
            $description = 'Creating environment file';

            $this->output->write('<comment>' . $description . '...</comment> ');

            copy(base_path('.env.example'), $path);

            $this->output->write('Done!' . PHP_EOL);

            $this->reloadEnvironment();
        }
    }

    /**
     * Run an Artisan command with some custom logging.
     *
     * @param $signature
     * @param $description
     */
    public function runArtisanCommand($signature, $description)
    {
        $write = $this->output->isVerbose() ? 'writeln' : 'write';

        $this->output->{$write}('<comment>' . $description . '...</comment> ');

        $output = $this->output->isVerbose() ? $this->output : new NullOutput();

        Artisan::call($signature, [], $output);

        $this->output->{$write}('Done!');

        $this->line('');
    }

    /**
     * Create a section of the setup script.
     *
     * @param string $title
     * @param Closure $contents
     */
    public function section($title, Closure $contents)
    {
        $this->comment($title. ':');

        $contents();
    }

    /**
     * Write some instruction text.
     *
     * @param $description
     */
    public function instruction($description)
    {
        // @TODO: Handle indentation in "section" method...
        $text = ' ' . $description;

        $this->output->writeln($text);
    }

    /**
     * Choose an environment variable from a list.
     *
     * @param $key
     * @param $choices
     * @param null $question
     */
    public function chooseEnvironmentVariable($key, $question = null, array $choices)
    {
        $question = is_null($question) ? 'Set a value for '.$key.':' : $question;

        $currentValue = env($key);

        if (! in_array($currentValue, $choices)) {
            array_unshift($choices, $currentValue);
        }

        $value = $this->choice($question, $choices, array_search($currentValue, $choices));

        $this->writeEnvironmentVariable($key, $value);
    }

    /**
     * Ask the user for the given environment variable.
     *
     * @param $key
     * @param null $question
     */
    public function setEnvironmentVariable($key, $question = null)
    {
        $question = is_null($question) ? 'Set a value for '.$key.':' : $question;

        $currentValue = env($key);

        $value = $this->askWithCompletion($question, [$currentValue], $currentValue);

        $this->writeEnvironmentVariable($key, $value);
    }

    /**
     * Write a new environment file with the given key.
     *
     * @param  string  $key
     * @return void
     */
    protected function writeEnvironmentVariable($key, $value)
    {
        $path = app()->environmentFilePath();

        $escaped = preg_quote('='.env($key), '/');

        $pattern = "/^{$key}{$escaped}/m";

        // Update the given key in the app's `.env` file.
        file_put_contents($path, preg_replace(
            $pattern, $key.'='.$value, file_get_contents($path)
        ));

        // Set the environment variable & reload the config repository.
        $this->reloadEnvironment();
    }

    /**
     * Reload the application & config after making changes.
     */
    protected function reloadEnvironment()
    {
        $adapters = [
            new EnvConstAdapter(),
            new PutenvAdapter(),
            new ServerConstAdapter(),
        ];

        $repository = RepositoryBuilder::create()
            ->withReaders($adapters)
            ->withWriters($adapters)
            ->make();

        // Reload the environment variables from the file.
        Dotenv::create($repository, app()->environmentPath(), null)->load();

        // Reload the config repository.
        $loader = new LoadConfiguration;

        $this->callSilent('config:clear');

        $loader->bootstrap(app());
    }
}
