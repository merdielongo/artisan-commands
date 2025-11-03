<?php

declare(strict_types=1);

namespace Davinet\ArtisanCommand\Commands;

use Illuminate\Support\Str;
use RuntimeException;

/**
 * Command to generate CRUD resources with Blade views.
 *
 * @author Merdi Elongo <merdielongo9@gmail.com>
 */
class CrudMakeCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:crud {name}
                            {--fields= : Comma-separated fields with their types (e.g., "title:string,name:string")}
                            {--force : Overwrite existing files without confirmation}
                            {--dry-run : Preview the file that would be created without actually creating it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a CRUD resource with Model, Controller, Repository, Service, and Blade views';

    /**
     * Parse fields into array.
     *
     * @param string|null $fields
     * @return array
     */
    protected function parseFields(?string $fields): array
    {
        if (empty($fields)) {
            return [];
        }

        $parsedFields = [];
        $fieldParts = explode(',', $fields);

        foreach ($fieldParts as $field) {
            $parts = explode(':', trim($field));
            if (count($parts) === 2) {
                $parsedFields[] = [
                    'name' => trim($parts[0]),
                    'type' => trim($parts[1])
                ];
            }
        }

        return $parsedFields;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $fields = $this->parseFields($this->option('fields'));

        if (empty($name)) {
            $this->error('The name of the CRUD resource is required.');
            return self::FAILURE;
        }

        try {
            $this->info("Generating CRUD for: {$name}");
            
            // Generate Model
            $this->generateModel($name, $fields);
            
            // Generate Repository
            $this->generateRepository($name);
            
            // Generate Service
            $this->generateService($name);
            
            // Generate Controller
            $this->generateController($name);
            
            // Generate Views
            $this->generateViews($name, $fields);
            
            // Generate Routes
            $this->generateRoutes($name);
            
            // Generate Tailwind Config if it doesn't exist
            $this->generateTailwindConfig();

            $this->newLine();
            $this->info('✓ CRUD generated successfully!');
            $this->line('  <comment>→</comment> All files are ready to use.');
            $this->line('  <comment>→</comment> Routes have been added to web.php');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Generate the Model.
     *
     * @param string $name
     * @param array $fields
     * @return void
     */
    protected function generateModel(string $name, array $fields): void
    {
        $stub = $this->getStubContent('crud.model');
        $stub = $this->replaceDummyModel(ucfirst($name), $stub);
        $stub = $this->replaceTableName(Str::snake(Str::plural($name)), $stub);
        
        // Add fillable fields
        if (!empty($fields)) {
            $fillableFields = array_map(function($field) {
                return "'" . $field['name'] . "'";
            }, $fields);
            $fillableString = implode(",\n        ", $fillableFields);
        } else {
            $fillableString = "// 'field1', 'field2'";
        }
        
        $stub = str_replace('DummyFillable', $fillableString, $stub);

        $path = app_path('Models/' . ucfirst($name) . '.php');
        
        if (!$this->shouldReplaceFile($path, "Model {$name} exists. Replace it? [y/n]")) {
            return;
        }

        $this->ensureDirectoryExists(app_path('Models'));
        $this->writeFile($path, $stub);
        $this->line('  ✓ Model created');
    }

    /**
     * Generate the Repository.
     *
     * @param string $name
     * @return void
     */
    protected function generateRepository(string $name): void
    {
        $modelName = ucfirst($name);
        $stub = $this->getStubContent('repository');
        $stub = $this->replaceModelNamespace('App', $stub);
        $stub = $this->replaceModelName($modelName, $stub);
        $stub = $this->replacePropertyName($modelName, $stub);
        $stub = str_replace('class DummyClass', 'class ' . $modelName . 'Repository', $stub);

        $path = app_path('Repositories/' . $modelName . 'Repository.php');
        
        if (!$this->shouldReplaceFile($path, "Repository {$name} exists. Replace it? [y/n]")) {
            return;
        }

        $this->ensureDirectoryExists(app_path('Repositories'));
        $this->writeFile($path, $stub);
        $this->line('  ✓ Repository created');
    }

    /**
     * Generate the Service.
     *
     * @param string $name
     * @return void
     */
    protected function generateService(string $name): void
    {
        $modelName = ucfirst($name);
        $stub = $this->getStubContent('crud.service');
        $stub = str_replace('DummyClass', $modelName . 'Service', $stub);
        $stub = str_replace('DummyModel', $modelName, $stub);
        $stub = str_replace('DummyRepository', $modelName . 'Repository', $stub);

        $path = app_path('Services/' . $modelName . 'Service.php');
        
        if (!$this->shouldReplaceFile($path, "Service {$name} exists. Replace it? [y/n]")) {
            return;
        }

        $this->ensureDirectoryExists(app_path('Services'));
        $this->writeFile($path, $stub);
        $this->line('  ✓ Service created');
    }

    /**
     * Generate the Controller.
     *
     * @param string $name
     * @return void
     */
    protected function generateController(string $name): void
    {
        $modelName = ucfirst($name);
        $variableName = lcfirst($name);
        $pluralVariable = Str::plural($variableName);
        $viewPath = Str::kebab(Str::plural($name));
        
        $stub = $this->getStubContent('crud.controller');
        $stub = str_replace('DummyController', $modelName . 'Controller', $stub);
        $stub = str_replace('DummyService', $modelName . 'Service', $stub);
        $stub = str_replace('DummyVariable', $variableName, $stub);
        $stub = str_replace('DummyPluralVariable', $pluralVariable, $stub);
        $stub = str_replace('DummyViewPath', $viewPath, $stub);

        $path = app_path('Http/Controllers/' . $modelName . 'Controller.php');
        
        if (!$this->shouldReplaceFile($path, "Controller {$name} exists. Replace it? [y/n]")) {
            return;
        }

        $this->ensureDirectoryExists(app_path('Http/Controllers'));
        $this->writeFile($path, $stub);
        $this->line('  ✓ Controller created');
    }

    /**
     * Generate the Views.
     *
     * @param string $name
     * @param array $fields
     * @return void
     */
    protected function generateViews(string $name, array $fields): void
    {
        $viewPath = Str::kebab(Str::plural($name));
        
        // Generate Index View
        $this->generateIndexView($viewPath, $name, $fields);
        
        // Generate Create View
        $this->generateCreateView($viewPath, $name, $fields);
        
        // Generate Edit View
        $this->generateEditView($viewPath, $name, $fields);
        
        // Generate Show View
        $this->generateShowView($viewPath, $name, $fields);
        
        // Generate layout
        $this->generateLayout();
    }

    /**
     * Generate Index View.
     *
     * @param string $viewPath
     * @param string $name
     * @param array $fields
     * @return void
     */
    protected function generateIndexView(string $viewPath, string $name, array $fields): void
    {
        $stub = $this->getStubContent('crud.views.index');
        $stub = str_replace('DummyResource', Str::plural(ucfirst($name)), $stub);
        $stub = str_replace('DummyResourceKebab', $viewPath, $stub);
        $pluralVariable = Str::plural(lcfirst($name));
        $stub = str_replace('$items', '$' . $pluralVariable, $stub);
        
        $path = resource_path('views/' . $viewPath . '/index.blade.php');
        
        if (!$this->shouldReplaceFile($path, "Index view exists. Replace it? [y/n]")) {
            return;
        }

        $this->ensureDirectoryExists(resource_path('views/' . $viewPath));
        $this->writeFile($path, $stub);
        $this->line('  ✓ Index view created');
    }

    /**
     * Generate Create View.
     *
     * @param string $viewPath
     * @param string $name
     * @param array $fields
     * @return void
     */
    protected function generateCreateView(string $viewPath, string $name, array $fields): void
    {
        $stub = $this->getStubContent('crud.views.create');
        $stub = str_replace('DummyResource', ucfirst($name), $stub);
        $stub = str_replace('DummyResourcePlural', Str::plural($name), $stub);
        $stub = str_replace('DummyResourceKebab', $viewPath, $stub);
        
        $formFields = $this->generateFormFields($fields);
        $stub = str_replace('DummyFormFields', $formFields, $stub);

        $path = resource_path('views/' . $viewPath . '/create.blade.php');
        
        if (!$this->shouldReplaceFile($path, "Create view exists. Replace it? [y/n]")) {
            return;
        }

        $this->writeFile($path, $stub);
        $this->line('  ✓ Create view created');
    }

    /**
     * Generate Edit View.
     *
     * @param string $viewPath
     * @param string $name
     * @param array $fields
     * @return void
     */
    protected function generateEditView(string $viewPath, string $name, array $fields): void
    {
        $stub = $this->getStubContent('crud.views.edit');
        $stub = str_replace('DummyResource', ucfirst($name), $stub);
        $stub = str_replace('DummyResourcePlural', Str::plural($name), $stub);
        $stub = str_replace('DummyResourceKebab', $viewPath, $stub);
        $variableName = lcfirst($name);
        $stub = str_replace('DummyVariable', $variableName, $stub);
        
        $formFields = $this->generateFormFields($fields, true, $variableName);
        $stub = str_replace('DummyFormFields', $formFields, $stub);

        $path = resource_path('views/' . $viewPath . '/edit.blade.php');
        
        if (!$this->shouldReplaceFile($path, "Edit view exists. Replace it? [y/n]")) {
            return;
        }

        $this->writeFile($path, $stub);
        $this->line('  ✓ Edit view created');
    }

    /**
     * Generate Show View.
     *
     * @param string $viewPath
     * @param string $name
     * @param array $fields
     * @return void
     */
    protected function generateShowView(string $viewPath, string $name, array $fields): void
    {
        $stub = $this->getStubContent('crud.views.show');
        $stub = str_replace('DummyResource', ucfirst($name), $stub);
        $stub = str_replace('DummyResourceKebab', $viewPath, $stub);
        $variableName = lcfirst($name);
        $stub = str_replace('DummyVariable', $variableName, $stub);
        
        $detailFields = $this->generateDetailFields($fields, $variableName);
        $stub = str_replace('DummyDetailFields', $detailFields, $stub);

        $path = resource_path('views/' . $viewPath . '/show.blade.php');
        
        if (!$this->shouldReplaceFile($path, "Show view exists. Replace it? [y/n]")) {
            return;
        }

        $this->writeFile($path, $stub);
        $this->line('  ✓ Show view created');
    }

    /**
     * Generate form fields (for Blade views).
     *
     * @param array $fields
     * @param bool $forEdit
     * @param string $variableName
     * @return string
     */
    protected function generateFormFields(array $fields, bool $forEdit = false, string $variableName = 'item'): string
    {
        if (empty($fields)) {
            return '            {{-- Add your form fields here --}}';
        }

        $formFields = [];
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            $fieldLabel = ucfirst(str_replace('_', ' ', $fieldName));
            $valueBinding = $forEdit ? "{{ old('{$fieldName}', \${$variableName}->{$fieldName}) }}" : "{{ old('{$fieldName}') }}";
            
            $formFields[] = $this->generateFieldByType($fieldName, $fieldLabel, $valueBinding, $field['type']);
        }

        return implode("\n", $formFields);
    }

    /**
     * Generate field HTML by type (for Blade views).
     *
     * @param string $fieldName
     * @param string $fieldLabel
     * @param string $valueBinding
     * @param string $type
     * @return string
     */
    protected function generateFieldByType(string $fieldName, string $fieldLabel, string $valueBinding, string $type): string
    {
        $id = "field_$fieldName";
        
        if (in_array($type, ['text', 'string', 'varchar'])) {
            return <<<HTML
            <div class="mb-4">
                <label for="$id" class="block text-gray-700 font-bold mb-2">$fieldLabel</label>
                <input type="text" id="$id" name="$fieldName" value="$valueBinding" 
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                       required>
            </div>
HTML;
        } elseif (in_array($type, ['textarea', 'text'])) {
            return <<<HTML
            <div class="mb-4">
                <label for="$id" class="block text-gray-700 font-bold mb-2">$fieldLabel</label>
                <textarea id="$id" name="$fieldName" rows="4"
                          class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                          required>$valueBinding</textarea>
            </div>
HTML;
        } elseif (in_array($type, ['email'])) {
            return <<<HTML
            <div class="mb-4">
                <label for="$id" class="block text-gray-700 font-bold mb-2">$fieldLabel</label>
                <input type="email" id="$id" name="$fieldName" value="$valueBinding" 
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                       required>
            </div>
HTML;
        } elseif (in_array($type, ['boolean', 'tinyint'])) {
            return <<<HTML
            <div class="mb-4">
                <label class="flex items-center">
                    <input type="checkbox" id="$id" name="$fieldName" value="1" 
                           class="mr-2">
                    <span class="text-gray-700">$fieldLabel</span>
                </label>
            </div>
HTML;
        } else {
            // Default input
            return <<<HTML
            <div class="mb-4">
                <label for="$id" class="block text-gray-700 font-bold mb-2">$fieldLabel</label>
                <input type="text" id="$id" name="$fieldName" value="$valueBinding" 
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                       required>
            </div>
HTML;
        }
    }

    /**
     * Generate detail fields (for Show view).
     *
     * @param array $fields
     * @param string $variableName
     * @return string
     */
    protected function generateDetailFields(array $fields, string $variableName = 'item'): string
    {
        if (empty($fields)) {
            return '            {{-- Add detail fields here --}}';
        }

        $detailFields = [];
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            $fieldLabel = ucfirst(str_replace('_', ' ', $fieldName));
            
            $detailFields[] = <<<HTML
            <div class="border-b border-gray-200 py-2">
                <dt class="text-gray-500 font-semibold">$fieldLabel:</dt>
                <dd class="text-gray-900">{{\${$variableName}->{$fieldName}}}</dd>
            </div>
HTML;
        }

        return implode("\n", $detailFields);
    }

    /**
     * Generate routes in web.php.
     *
     * @param string $name
     * @return void
     */
    protected function generateRoutes(string $name): void
    {
        $routesPath = base_path('routes/web.php');
        
        // Check if routes file exists
        if (!file_exists($routesPath)) {
            $this->warn('  ⚠ routes/web.php not found. Routes not added.');
            return;
        }

        $resourceName = Str::plural(ucfirst($name));
        $controllerName = ucfirst($name) . 'Controller';
        $routePrefix = Str::kebab(Str::plural($name));

        $stub = $this->getStubContent('crud.routes');
        $stub = str_replace('{{ $resourceName }}', $resourceName, $stub);
        $stub = str_replace('{{ $controllerName }}', $controllerName, $stub);
        $stub = str_replace('{{ $routePrefix }}', $routePrefix, $stub);

        // Read current routes file
        $routesContent = file_get_contents($routesPath);

        // Check if routes already exist
        if (str_contains($routesContent, $routePrefix)) {
            $this->line('  ⚠ Routes already exist in web.php. Skipping.');
            return;
        }

        // Append routes to the end of the file
        $routesContent .= "\n" . $stub;

        // Write back
        if (!$this->option('dry-run')) {
            file_put_contents($routesPath, $routesContent);
            $this->line('  ✓ Routes added to web.php');
        } else {
            $this->line('[DRY RUN] Would add routes to web.php');
        }
    }

    /**
     * Generate Layout.
     *
     * @return void
     */
    protected function generateLayout(): void
    {
        $path = resource_path('views/layouts/app.blade.php');
        
        if (file_exists($path)) {
            return; // Layout already exists
        }

        $stub = $this->getStubContent('crud.layout');
        
        if (!$this->shouldReplaceFile($path, "Layout exists. Replace it? [y/n]")) {
            return;
        }

        $this->ensureDirectoryExists(resource_path('views/layouts'));
        $this->writeFile($path, $stub);
        $this->line('  ✓ Layout created');
    }

    /**
     * Generate Tailwind Config.
     *
     * @return void
     */
    protected function generateTailwindConfig(): void
    {
        $path = base_path('tailwind.config.js');
        
        if (file_exists($path)) {
            return; // Config already exists
        }

        $stub = $this->getStubContent('tailwind.config');
        
        if (!$this->shouldReplaceFile($path, "Tailwind config exists. Replace it? [y/n]")) {
            return;
        }

        $this->writeFile($path, $stub);
        $this->line('  ✓ Tailwind config created');
    }

    /**
     * Replace DummyModel in stub.
     *
     * @param string $name
     * @param string $stub
     * @return string
     */
    protected function replaceDummyModel(string $name, string $stub): string
    {
        return str_replace('DummyModel', $name, $stub);
    }

    /**
     * Replace Table Name in stub.
     *
     * @param string $name
     * @param string $stub
     * @return string
     */
    protected function replaceTableName(string $name, string $stub): string
    {
        return str_replace('DummyTable', $name, $stub);
    }

    /**
     * Ensure directory exists.
     *
     * @param string $directory
     * @return void
     */
    protected function ensureDirectoryExists(string $directory): void
    {
        if (!file_exists($directory)) {
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new RuntimeException("Unable to create directory: {$directory}");
            }
        }
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
}
