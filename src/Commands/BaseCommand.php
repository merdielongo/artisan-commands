<?php

declare(strict_types=1);

namespace Davinet\ArtisanCommand\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Base command class with common functionality for all artisan commands.
 *
 * @author Merdi Elongo <merdielongo9@gmail.com>
 */
abstract class BaseCommand extends Command
{
    /**
     * Check if the filename exists and if it could be replaced.
     *
     * @param string $filename The full path to the file
     * @param string $question The question to ask if file exists
     * @return bool True if file should be replaced, false otherwise
     */
    protected function shouldReplaceFile(string $filename, string $question): bool
    {
        if (!$this->fileExists($filename)) {
            return true;
        }

        // Skip confirmation if --force option is present
        if ($this->option('force')) {
            return true;
        }

        // Skip if --dry-run option is present
        if ($this->option('dry-run')) {
            $this->warn("[DRY RUN] File exists: {$filename}");
            return false;
        }

        // Ask for confirmation
        do {
            $input = $this->ask($question);
        } while (!in_array(strtolower($input), ['y', 'yes', 'n', 'no'], true));

        return in_array(strtolower($input), ['y', 'yes'], true);
    }

    /**
     * Check if a file exists (handles both Windows and Unix paths).
     *
     * @param string $filename
     * @return bool
     */
    protected function fileExists(string $filename): bool
    {
        if (File::exists($filename)) {
            return true;
        }

        // Check alternative path separators
        $otherPath = str_contains($filename, '\\')
            ? str_replace('\\', '/', $filename)
            : str_replace('/', '\\', $filename);

        return File::exists($otherPath);
    }

    /**
     * Create a set of folders if necessary.
     *
     * @param array $pathParts Array of folder names
     * @param string $basePath Base path where folders should be created
     * @return void
     */
    protected function createFoldersIfNecessary(array $pathParts, string $basePath): void
    {
        $folder = $basePath;

        for ($i = 0; $i < count($pathParts) - 1; $i++) {
            $folderPath = $folder . DIRECTORY_SEPARATOR . $pathParts[$i];

            if (!File::isDirectory($folderPath)) {
                try {
                    File::makeDirectory($folderPath, 0755, true);
                } catch (\Exception $e) {
                    throw new RuntimeException(
                        "Unable to create directory: {$folderPath}. Error: {$e->getMessage()}"
                    );
                }
            }

            $folder = $folderPath;
        }
    }

    /**
     * Write content to a file with error handling.
     *
     * @param string $path Full path to the file
     * @param string $content Content to write
     * @return void
     * @throws RuntimeException
     */
    protected function writeFile(string $path, string $content): void
    {
        if ($this->option('dry-run')) {
            $this->line("[DRY RUN] Would create file: {$path}");
            $this->line("[DRY RUN] Content length: " . strlen($content) . " bytes");
            return;
        }

        try {
            File::put($path, $content);
        } catch (\Exception $e) {
            throw new RuntimeException(
                "Unable to write file: {$path}. Error: {$e->getMessage()}"
            );
        }
    }

    /**
     * Get stub content from file.
     *
     * @param string $stubName Name of the stub file (without .stub extension)
     * @return string The stub content
     * @throws RuntimeException
     */
    protected function getStubContent(string $stubName): string
    {
        $stubPath = __DIR__ . '/stubs/' . $stubName . '.stub';

        if (!File::exists($stubPath)) {
            throw new RuntimeException("Stub file not found: {$stubPath}");
        }

        $content = File::get($stubPath);

        if ($content === false) {
            throw new RuntimeException("Unable to read stub file: {$stubPath}");
        }

        return $content;
    }

    /**
     * Validate filename to prevent path traversal attacks.
     *
     * @param string $filename
     * @param string $pattern Regex pattern for validation
     * @return bool
     */
    protected function isValidFilename(string $filename, string $pattern = '#^[a-zA-Z][a-zA-Z0-9._\\-\\\\/]+$#'): bool
    {
        // Prevent path traversal
        if (str_contains($filename, '..')) {
            return false;
        }

        return (bool) preg_match($pattern, $filename);
    }

    /**
     * Display success message with file path.
     *
     * @param string $message
     * @param string|null $filePath
     * @return void
     */
    protected function displaySuccess(string $message, ?string $filePath = null): void
    {
        $this->info($message);

        if ($filePath !== null && !$this->option('dry-run')) {
            $this->line("  <comment>→</comment> {$filePath}");
        }
    }

    /**
     * Replace placeholders in stub content.
     *
     * @param string $stub The stub content
     * @param array $replacements Array of ['placeholder' => 'replacement']
     * @return string
     */
    protected function replacePlaceholders(string $stub, array $replacements): string
    {
        foreach ($replacements as $placeholder => $replacement) {
            $stub = str_replace($placeholder, $replacement, $stub);
        }

        return $stub;
    }
}

