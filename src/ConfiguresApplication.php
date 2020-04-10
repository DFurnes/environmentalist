<?php

namespace DFurnes\Environmentalist;

use Closure;
use Dotenv\Dotenv;
use Dotenv\Environment\DotenvFactory;
use Dotenv\Exception\InvalidFileException;
use Dotenv\Environment\Adapter\PutenvAdapter;
use Dotenv\Environment\Adapter\EnvConstAdapter;
use Dotenv\Environment\Adapter\ServerConstAdapter;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;

/** @mixin \Illuminate\Console\Command */
trait ConfiguresApplication
{
    /**
     * Create the '.env' from '.env.example'.
     *
     * @param $reset
     */
    public function createEnvironmentFile($reset)
    {
        $path = app()->environmentFilePath();
        $shouldCreateFile = ! file_exists($path) || $reset;

        if ($shouldCreateFile) {
            $description = 'Creating environment file';
            $this->output->write('<comment>' . $description . '...</comment> ');
            copy(base_path('.env.example'), $path);
            $this->output->write('Done!' . PHP_EOL);

            $this->reloadEnvironment();
        }
    }

    /**
     * Run an Artisan command.
     *
     * @param $command
     * @param $description
     */
    protected function runCommand($command, $description) {
        $call = $this->output->isVerbose() ? 'call' : 'callSilent';
        $write = $this->output->isVerbose() ? 'writeln' : 'write';

        $this->output->{$write}('<comment>' . $description . '...</comment> ');
        $this->{$call}($command);
        $this->output->{$write}('Done!');

        $this->line('');
    }

    /**
     * Create a section of the setup script.
     *
     * @param $title
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
        // Reload the environment variables from the file.
        $factory = new DotenvFactory([new EnvConstAdapter, new ServerConstAdapter, new PutenvAdapter]);
        Dotenv::create($app->environmentPath(), $app->environmentFile(), $factory)->load();

        $env->load();

        // Reload the config repository.
        $loader = new LoadConfiguration;
        $this->callSilent('config:clear');
        $loader->bootstrap(app());
    }
}
