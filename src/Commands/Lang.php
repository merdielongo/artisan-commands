<?php

declare(strict_types=1);

namespace Davinet\ArtisanCommand\Commands;

/**
 * Command to generate language files.
 *
 * @author Merdi Elongo <merdielongo9@gmail.com>
 */
class Lang extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:lang {name?} 
                            {--locale= : The targeted locale (default: en)}
                            {--json : Create a JSON language file}
                            {--force : Overwrite existing files without confirmation}
                            {--dry-run : Preview the file that would be created without actually creating it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new language file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $name = $this->hasArgument('name') ? $this->argument('name') : '';
        $locale = $this->option('locale') ?? 'en';

        if ($this->option('json')) {
            return $this->createJson($locale);
        }

        if (empty($name)) {
            $this->error('No filename is given. Use --json flag for JSON language files.');
            return self::FAILURE;
        }

        if (!$this->nameIsCorrect($name)) {
            $this->error('The given filename is not correct. Only alphanumeric characters are allowed.');
            return self::FAILURE;
        }

        return $this->createLang($name, $locale);
    }

    /**
     * Create a locale file within a lang sub-folder.
     *
     * @param string $name
     * @param string $locale
     * @return int
     */
    protected function createLang(string $name, string $locale): int
    {
        try {
            $path = $this->getLangPath($locale, $name . '.php');
            $question = "There is already a locale file with this name. Do you want to replace it? [y/n]";

            if (!$this->shouldReplaceFile($path, $question)) {
                return self::SUCCESS;
            }

            $this->ensureLocaleDirectoryExists($locale);

            $stub = $this->getStubContent('lang');
            $this->writeFile($path, $stub);

            $this->displaySuccess('Language file created successfully!', $path);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Create a JSON locale file.
     *
     * @param string $locale
     * @return int
     */
    protected function createJson(string $locale): int
    {
        try {
            $path = $this->getLangPath($locale . '.json');
            $question = "There is already a locale file with this name. Do you want to replace it? [y/n]";

            if (!$this->shouldReplaceFile($path, $question)) {
                return self::SUCCESS;
            }

            $content = "{\n    \n}";
            $this->writeFile($path, $content);

            $this->displaySuccess('Language file created successfully!', $path);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Get the path to a language file.
     *
     * @param string ...$parts
     * @return string
     */
    protected function getLangPath(string ...$parts): string
    {
        return resource_path('lang' . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts));
    }

    /**
     * Ensure the locale directory exists.
     *
     * @param string $locale
     * @return void
     */
    protected function ensureLocaleDirectoryExists(string $locale): void
    {
        $directory = resource_path('lang' . DIRECTORY_SEPARATOR . $locale);

        if (!file_exists($directory)) {
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new \RuntimeException("Unable to create directory: {$directory}");
            }
        }
    }

    /**
     * Check if the name is correct.
     *
     * @param string $name
     * @return bool
     */
    protected function nameIsCorrect(string $name): bool
    {
        return (bool) preg_match('#^[a-zA-Z][a-zA-Z0-9]+$#', $name);
    }
}
