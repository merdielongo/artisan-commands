<?php

declare(strict_types=1);

namespace Davinet\ArtisanCommand\Commands;

use Illuminate\Support\Str;
use RuntimeException;

/**
 * Command to generate classes, traits, or interfaces.
 *
 * @author Merdi Elongo <merdielongo9@gmail.com>
 */
class ClassMakeCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:class {filename}
                            {--kind=class : The type of file to create (class, trait, or interface)}
                            {--separator=\\ : Character used to separate file and its parent folder(s)}
                            {--force : Overwrite existing files without confirmation}
                            {--dry-run : Preview the file that would be created without actually creating it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new class, trait, or interface file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $filename = $this->argument('filename');

        if (!$this->isValidFilename($filename)) {
            $this->error('The filename is not correct. Only alphanumeric characters, dots, underscores, backslashes, and hyphens are allowed.');
            return self::FAILURE;
        }

        $kind = $this->getKind();

        if ($kind === null) {
            return self::FAILURE;
        }

        try {
            $path = $this->buildFilePath($filename);
            $question = "There is already a file with this name. Do you want to replace it? [y/n]";

            if (!$this->shouldReplaceFile($path, $question)) {
                return self::SUCCESS;
            }

            $pathParts = $this->splitPath($filename);
            $basePath = base_path();
            
            $this->createFoldersIfNecessary($pathParts, $basePath);

            $stub = $this->getStubContent($kind);
            $className = $pathParts[count($pathParts) - 1];
            $namespace = $this->buildNamespace($pathParts);

            $stub = $this->replaceKindName($kind, $className, $stub);
            $stub = $this->replaceNamespace($namespace, $stub);

            $this->writeFile($path, $stub);

            $this->displaySuccess(ucfirst($kind) . ' created successfully!', $path);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Build the full file path.
     *
     * @param string $filename
     * @return string
     */
    protected function buildFilePath(string $filename): string
    {
        return base_path($filename . '.php');
    }

    /**
     * Split the filename into path parts.
     *
     * @param string $filename
     * @return array
     */
    protected function splitPath(string $filename): array
    {
        if (str_contains($filename, '/')) {
            return explode('/', $filename);
        }

        return explode('\\', $filename);
    }

    /**
     * Build namespace from path parts.
     *
     * @param array $pathParts
     * @return string
     */
    protected function buildNamespace(array $pathParts): string
    {
        $namespace = '';

        for ($i = 0; $i < count($pathParts) - 1; $i++) {
            $namespace .= ucfirst($pathParts[$i]) . '\\';
        }

        return Str::replaceLast('\\', '', $namespace);
    }

    /**
     * Replace every Dummy[Kind] with the right [kind] name.
     *
     * @param string $kind
     * @param string $name
     * @param string $stub
     * @return string
     */
    protected function replaceKindName(string $kind, string $name, string $stub): string
    {
        return str_replace('Dummy' . ucfirst($kind), ucfirst($name), $stub);
    }

    /**
     * Set the right namespace in the stub.
     *
     * @param string $namespace
     * @param string $stub
     * @return string
     */
    protected function replaceNamespace(string $namespace, string $stub): string
    {
        if (!empty($namespace)) {
            return str_replace('DummyNamespace', 'namespace ' . $namespace . ';', $stub);
        }

        return str_replace('DummyNamespace', '', $stub);
    }

    /**
     * Get the kind option's value.
     *
     * @return string|null
     */
    protected function getKind(): ?string
    {
        $kind = $this->option('kind');

        if ($kind === null) {
            return 'class';
        }

        $validKinds = ['class', 'trait', 'interface'];

        if (!in_array($kind, $validKinds, true)) {
            $this->error("Invalid kind value. The kind must be one of: " . implode(', ', $validKinds));
            return null;
        }

        return $kind;
    }
}
