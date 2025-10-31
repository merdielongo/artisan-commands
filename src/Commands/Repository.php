<?php

declare(strict_types=1);

namespace Davinet\ArtisanCommand\Commands;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Command to generate repository classes.
 *
 * @author Merdi Elongo <merdielongo9@gmail.com>
 */
class Repository extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:repository {name} 
                            {--model= : The model on which the repository class will be based}
                            {--force : Overwrite existing files without confirmation}
                            {--dry-run : Preview the file that would be created without actually creating it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new repository class';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $model = $this->option('model');

        if (empty($name)) {
            $this->error('The name of the repository is required.');
            return self::FAILURE;
        }

        try {
            $content = $this->buildContent($name, $model);

            if ($content === null) {
                return self::FAILURE;
            }

            $filename = app_path('Repositories/' . ucfirst($name) . '.php');
            $question = "There is a repository with this name ({$name}). Do you want to replace it? [y/n]";

            if (!$this->shouldReplaceFile($filename, $question)) {
                return self::SUCCESS;
            }

            $this->ensureDirectoryExists();
            $this->writeFile($filename, $content);

            $this->displaySuccess('Repository created successfully!', $filename);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Build the content for the repository file.
     *
     * @param string $name
     * @param string|null $model
     * @return string|null
     */
    protected function buildContent(string $name, ?string $model): ?string
    {
        if ($model === null) {
            return $this->replaceClassName($name, $this->getStubContent('empty.repository'));
        }

        $namespace = 'App';
        $modelName = $model;

        if (Str::contains($model, ['\\', '/'])) {
            $this->setModelAndNamespace($modelName, $namespace);
        }

        if (!$this->modelFileExists($namespace . '\\' . $modelName)) {
            $this->error("The specified model \"{$this->option('model')}\" does not exist.");
            return null;
        }

        $stub = $this->getStubContent('repository');
        $stub = $this->replaceModelNamespace($namespace, $stub);
        $stub = $this->replaceModelName($modelName, $stub);
        $stub = $this->replacePropertyName($modelName, $stub);
        $stub = $this->replaceClassName($name, $stub);

        return $stub;
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
     * Replace every DummyProperty with the right property name.
     *
     * @param string $name
     * @param string $stub
     * @return string
     */
    protected function replacePropertyName(string $name, string $stub): string
    {
        $property = lcfirst(Str::camel($name));
        return str_replace('DummyProperty', $property, $stub);
    }

    /**
     * Replace every DummyModel with the right model name.
     *
     * @param string $name
     * @param string $stub
     * @return string
     */
    protected function replaceModelName(string $name, string $stub): string
    {
        return str_replace('DummyModel', ucfirst($name), $stub);
    }

    /**
     * Replace the namespace of the model.
     *
     * @param string $namespace
     * @param string $stub
     * @return string
     */
    protected function replaceModelNamespace(string $namespace, string $stub): string
    {
        return str_replace('DummyModelNamespace', ucfirst($namespace), $stub);
    }

    /**
     * Set the right name and namespace from model string.
     *
     * @param string &$model
     * @param string &$namespace
     * @return void
     */
    protected function setModelAndNamespace(string &$model, string &$namespace): void
    {
        $exploded = str_contains($model, '/') ? explode('/', $model) : explode('\\', $model);
        $model = Arr::last($exploded);
        $namespace = '';

        for ($i = 0; $i < count($exploded) - 1; $i++) {
            $namespace .= $exploded[$i] . '\\';
        }

        $namespace = Str::replaceLast('\\', '', $namespace);
    }

    /**
     * Check if a model file exists.
     *
     * @param string $model
     * @return bool
     */
    protected function modelFileExists(string $model): bool
    {
        $paths = [
            base_path(lcfirst($model) . '.php'),
            base_path(lcfirst(str_replace('\\', '/', $model)) . '.php'),
            app_path(str_replace('\\', '/', $model) . '.php'),
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return true;
            }
        }

        // Try to find using autoloader
        try {
            if (class_exists($model)) {
                $reflection = new \ReflectionClass($model);
                return $reflection->getFileName() !== false;
            }
        } catch (\ReflectionException $e) {
            // Class doesn't exist
        }

        return false;
    }

    /**
     * Ensure the Repositories directory exists.
     *
     * @return void
     */
    protected function ensureDirectoryExists(): void
    {
        $directory = app_path('Repositories');

        if (!file_exists($directory)) {
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new RuntimeException("Unable to create directory: {$directory}");
            }
        }
    }
}
