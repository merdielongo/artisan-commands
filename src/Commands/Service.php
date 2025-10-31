<?php

declare(strict_types=1);

namespace Davinet\ArtisanCommand\Commands;

use RuntimeException;

/**
 * Command to generate service classes.
 *
 * @author Merdi Elongo <merdielongo9@gmail.com>
 */
class Service extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:service {name}
                            {--force : Overwrite existing files without confirmation}
                            {--dry-run : Preview the file that would be created without actually creating it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new service class';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $name = $this->argument('name');

        if (empty($name)) {
            $this->error('The name of the service is required.');
            return self::FAILURE;
        }

        try {
            $stub = $this->getStubContent('empty.service');
            $content = $this->replaceClassName($name, $stub);

            $filename = app_path('Services/' . ucfirst($name) . '.php');
            $question = "There is a service with this name ({$name}). Do you want to replace it? [y/n]";

            if (!$this->shouldReplaceFile($filename, $question)) {
                return self::SUCCESS;
            }

            $this->ensureDirectoryExists();
            $this->writeFile($filename, $content);

            $this->displaySuccess('Service created successfully!', $filename);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Replace every DummyClass with the right class name.
     *
     * @param string $name
     * @param string $stub
     * @return string
     */
    protected function replaceClassName(string $name, string $stub): string
    {
        return str_replace('DummyClass', ucfirst($name), $stub);
    }

    /**
     * Ensure the Services directory exists.
     *
     * @return void
     */
    protected function ensureDirectoryExists(): void
    {
        $directory = app_path('Services');

        if (!file_exists($directory)) {
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new RuntimeException("Unable to create directory: {$directory}");
            }
        }
    }
}
