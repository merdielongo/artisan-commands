<?php

declare(strict_types=1);

namespace Davinet\ArtisanCommand\Commands;

/**
 * Command to generate Blade view files.
 *
 * @author Merdi Elongo <merdielongo9@gmail.com>
 */
class View extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:view {name} 
                            {--layout= : The layout that the view will extend}
                            {--force : Overwrite existing files without confirmation}
                            {--dry-run : Preview the file that would be created without actually creating it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Blade view file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $name = $this->argument('name');

        if (!preg_match('#^[a-zA-Z._\-0-9]+$#', $name)) {
            $this->error('Invalid view name. Only alphanumeric characters, dots, underscores, and dashes are supported.');
            return self::FAILURE;
        }

        try {
            $pathParts = explode('.', $name);
            $folder = resource_path('views');

            $this->createFoldersIfNecessary($pathParts, $folder);

            $fileIndex = count($pathParts) - 1;
            $filename = $folder . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, array_slice($pathParts, 0, $fileIndex)) 
                      . DIRECTORY_SEPARATOR . $pathParts[$fileIndex] . '.blade.php';

            // Normalize path separators
            $filename = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filename);

            $content = $this->buildContent();
            $question = "There is a view with this name. Do you want to replace it? [y/n]";

            if (!$this->shouldReplaceFile($filename, $question)) {
                return self::SUCCESS;
            }

            $this->writeFile($filename, $content);

            $this->displaySuccess('View created successfully!', $filename);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Build the content for the view file.
     *
     * @return string
     */
    protected function buildContent(): string
    {
        $layout = $this->option('layout');

        if (empty($layout)) {
            return '';
        }

        $stub = $this->getStubContent('view');
        return $this->replaceLayout($layout, $stub);
    }

    /**
     * Fill the right layout name in the stub.
     *
     * @param string $layout
     * @param string $stub
     * @return string
     */
    protected function replaceLayout(string $layout, string $stub): string
    {
        return str_replace('Layout', $layout, $stub);
    }
}
