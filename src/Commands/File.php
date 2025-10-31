<?php

declare(strict_types=1);

namespace Davinet\ArtisanCommand\Commands;

use Illuminate\Support\Str;

/**
 * Command to generate generic files.
 *
 * @author Merdi Elongo <merdielongo9@gmail.com>
 */
class File extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:file {filename} 
                            {--ext= : The file extension (default: php)}
                            {--force : Overwrite existing files without confirmation}
                            {--dry-run : Preview the file that would be created without actually creating it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new generic file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $filename = $this->argument('filename');

        if (!$this->isValidFilename($filename)) {
            $this->error('The filename is not correct. Only alphanumeric characters, dots, underscores, and hyphens are allowed.');
            return self::FAILURE;
        }

        try {
            $extension = $this->getExtension();
            $path = base_path(str_replace('.', DIRECTORY_SEPARATOR, $filename) . '.' . $extension);

            $question = "There is already a file with this name. Do you want to replace it? [y/n]";

            if (!$this->shouldReplaceFile($path, $question)) {
                return self::SUCCESS;
            }

            $pathParts = explode('.', $filename);
            $this->createFoldersIfNecessary($pathParts, base_path());

            $this->writeFile($path, '');

            $this->displaySuccess('File created successfully!', $path);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Get the file extension specified by the option.
     * PHP is considered as the default extension.
     *
     * @return string
     */
    protected function getExtension(): string
    {
        $ext = $this->option('ext');

        if ($ext === null) {
            return 'php';
        }

        // Remove leading dot if present
        if (Str::startsWith($ext, '.')) {
            return Str::replaceFirst('.', '', $ext);
        }

        return $ext;
    }
}
