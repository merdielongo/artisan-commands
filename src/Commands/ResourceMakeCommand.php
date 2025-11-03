<?php

declare(strict_types=1);

namespace Davinet\ArtisanCommand\Commands;

use Illuminate\Support\Str;
use RuntimeException;

/**
 * Command to generate Resource-based CRUD (Filament-inspired pattern).
 *
 * @author Merdi Elongo <merdielongo9@gmail.com>
 */
class ResourceMakeCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:resource-crud {name}
                            {--fields= : Comma-separated fields with their types (e.g., "title:string,name:string")}
                            {--force : Overwrite existing files without confirmation}
                            {--dry-run : Preview the file that would be created without actually creating it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a CRUD resource with Resource classes (Filament-inspired pattern)';

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
            $this->info("Generating Resource CRUD for: {$name}");
            
            // Generate base classes first (if they don't exist)
            $this->generateBaseClasses();
            
            // Generate Model
            $this->generateModel($name, $fields);
            
            // Generate Repository
            $this->generateRepository($name);
            
            // Generate Service
            $this->generateService($name);
            
            // Generate Controller
            $this->generateController($name);
            
            // Generate Resource Class
            $this->generateResource($name, $fields);
            
            // Generate default views that use Resource class
            $this->generateDefaultViews($name, $fields);
            
            // Generate Routes
            $this->generateRoutes($name);
            
            // Generate Tailwind Config if it doesn't exist
            $this->generateTailwindConfig();

            $this->newLine();
            $this->info('✓ Resource CRUD generated successfully!');
            $this->line('  <comment>→</comment> All files are ready to use.');
            $this->line('  <comment>→</comment> Routes have been added to web.php');
            $this->line('  <comment>→</comment> Using Resource class pattern (Laravel Filament-inspired)');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Generate base classes (Resource, Form, Table).
     *
     * @return void
     */
    protected function generateBaseClasses(): void
    {
        $this->info('  Checking base classes...');
        
        // Generate base Resource class
        $this->generateBaseResource();
        
        // Generate Form builder
        $this->generateFormBuilder();
        
        // Generate Table builder
        $this->generateTableBuilder();
    }

    /**
     * Generate base Resource class.
     *
     * @return void
     */
    protected function generateBaseResource(): void
    {
        $path = app_path('Resources/Resource.php');
        
        if (file_exists($path)) {
            return; // Already exists
        }

        $stub = $this->getStubContent('resource.base');
        
        if (!$this->shouldReplaceFile($path, "Base Resource exists. Replace it? [y/n]")) {
            return;
        }

        $this->ensureDirectoryExists(app_path('Resources'));
        $this->writeFile($path, $stub);
        $this->line('  ✓ Base Resource class created');
    }

    /**
     * Generate Form builder.
     *
     * @return void
     */
    protected function generateFormBuilder(): void
    {
        $path = app_path('Builders/Form.php');
        
        if (file_exists($path)) {
            return; // Already exists
        }

        $stub = $this->getStubContent('form.builder');
        
        if (!$this->shouldReplaceFile($path, "Form builder exists. Replace it? [y/n]")) {
            return;
        }

        $this->ensureDirectoryExists(app_path('Builders'));
        $this->writeFile($path, $stub);
        $this->line('  ✓ Form builder created');
    }

    /**
     * Generate Table builder.
     *
     * @return void
     */
    protected function generateTableBuilder(): void
    {
        $path = app_path('Builders/Table.php');
        
        if (file_exists($path)) {
            return; // Already exists
        }

        $stub = $this->getStubContent('table.builder');
        
        if (!$this->shouldReplaceFile($path, "Table builder exists. Replace it? [y/n]")) {
            return;
        }

        $this->ensureDirectoryExists(app_path('Builders'));
        $this->writeFile($path, $stub);
        $this->line('  ✓ Table builder created');
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
        $viewPath = 'resources.crud';
        
        $stub = $this->getStubContent('crud.controller.resource');
        $stub = str_replace('DummyController', $modelName . 'Controller', $stub);
        $stub = str_replace('DummyService', $modelName . 'Service', $stub);
        $stub = str_replace('DummyVariable', $variableName, $stub);
        $stub = str_replace('DummyPluralVariable', $pluralVariable, $stub);
        $stub = str_replace('DummyViewPath', $viewPath, $stub);
        $stub = str_replace('DummyResourceClass', $modelName . 'Resource', $stub);

        $path = app_path('Http/Controllers/' . $modelName . 'Controller.php');
        
        if (!$this->shouldReplaceFile($path, "Controller {$name} exists. Replace it? [y/n]")) {
            return;
        }

        $this->ensureDirectoryExists(app_path('Http/Controllers'));
        $this->writeFile($path, $stub);
        $this->line('  ✓ Controller created');
    }

    /**
     * Generate the Resource Class.
     *
     * @param string $name
     * @param array $fields
     * @return void
     */
    protected function generateResource(string $name, array $fields): void
    {
        $modelName = ucfirst($name);
        $resourceName = Str::plural($modelName);
        $routePrefix = Str::kebab(Str::plural($name));
        
        $stub = $this->getStubContent('crud.resource');
        $stub = str_replace('DummyResourceName', $resourceName, $stub);
        $stub = str_replace('DummyResourceClass', $modelName . 'Resource', $stub);
        $stub = str_replace('DummyModel', $modelName, $stub);
        $stub = str_replace('DummyResourcePlural', Str::plural($modelName), $stub);
        $stub = str_replace('DummyResourceKebab', $routePrefix, $stub);
        
        // Generate form fields
        $formFields = $this->generateResourceFormFields($fields);
        $stub = str_replace('DummyFormFields', $formFields, $stub);
        
        // Generate table columns
        $tableColumns = $this->generateResourceTableColumns($fields);
        $stub = str_replace('DummyTableColumns', $tableColumns, $stub);
        
        // Generate validation rules
        $validationRules = $this->generateValidationRules($fields);
        $stub = str_replace('DummyValidationRules', $validationRules, $stub);

        $path = app_path('Resources/' . $modelName . 'Resource.php');
        
        if (!$this->shouldReplaceFile($path, "Resource {$name} exists. Replace it? [y/n]")) {
            return;
        }

        $this->ensureDirectoryExists(app_path('Resources'));
        $this->writeFile($path, $stub);
        $this->line('  ✓ Resource class created');
    }

    /**
     * Generate form fields for Resource.
     *
     * @param array $fields
     * @return string
     */
    protected function generateResourceFormFields(array $fields): string
    {
        if (empty($fields)) {
            return "            // Form::text('name', 'Name'),";
        }

        $formFields = [];
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            $fieldLabel = ucfirst(str_replace('_', ' ', $fieldName));
            $type = $this->getFormFieldType($field['type']);
            
            $formFields[] = "            Form::{$type}('{$fieldName}', '{$fieldLabel}'),";
        }

        return implode("\n", $formFields);
    }

    /**
     * Generate table columns for Resource.
     *
     * @param array $fields
     * @return string
     */
    protected function generateResourceTableColumns(array $fields): string
    {
        if (empty($fields)) {
            return "            // Table::text('id', 'ID'),";
        }

        $columns = [
            "            Table::text('id', 'ID'),"
        ];
        
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            $fieldLabel = ucfirst(str_replace('_', ' ', $fieldName));
            $type = $this->getTableColumnType($field['type']);
            
            $columns[] = "            Table::{$type}('{$fieldName}', '{$fieldLabel}'),";
        }

        $columns[] = "            Table::actions(),";
        
        return implode("\n", $columns);
    }

    /**
     * Generate validation rules.
     *
     * @param array $fields
     * @return string
     */
    protected function generateValidationRules(array $fields): string
    {
        if (empty($fields)) {
            return "            // 'field' => 'required',";
        }

        $rules = [];
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            $rules[] = "            '{$fieldName}' => 'required',";
        }

        return implode("\n", $rules);
    }

    /**
     * Get form field type from database type.
     *
     * @param string $type
     * @return string
     */
    protected function getFormFieldType(string $type): string
    {
        $typeMap = [
            'string' => 'text',
            'text' => 'textarea',
            'textarea' => 'textarea',
            'email' => 'email',
            'boolean' => 'checkbox',
            'tinyint' => 'checkbox',
        ];

        return $typeMap[strtolower($type)] ?? 'text';
    }

    /**
     * Get table column type from database type.
     *
     * @param string $type
     * @return string
     */
    protected function getTableColumnType(string $type): string
    {
        $typeMap = [
            'decimal' => 'number',
            'integer' => 'number',
            'bigint' => 'number',
            'boolean' => 'boolean',
            'tinyint' => 'boolean',
            'datetime' => 'datetime',
            'date' => 'datetime',
            'timestamp' => 'datetime',
        ];

        return $typeMap[strtolower($type)] ?? 'text';
    }

    /**
     * Generate default views that use Resource class.
     *
     * @param string $name
     * @param array $fields
     * @return void
     */
    protected function generateDefaultViews(string $name, array $fields): void
    {
        $viewPath = 'resources.crud';
        $resourceName = Str::plural(ucfirst($name));
        $routePrefix = Str::kebab(Str::plural($name));
        
        // Ensure crud views directory exists
        $this->ensureDirectoryExists(resource_path('views/resources/crud'));
        
        // Generate _form.blade.php
        $this->generateDefaultFormView($name, $fields, $viewPath);
        
        // Generate index.blade.php
        $this->generateDefaultIndexView($name, $fields, $viewPath, $resourceName, $routePrefix);
        
        // Generate create.blade.php
        $this->generateDefaultCreateView($name, $viewPath, $resourceName, $routePrefix);
        
        // Generate edit.blade.php
        $this->generateDefaultEditView($name, $viewPath, $resourceName, $routePrefix);
        
        // Generate show.blade.php
        $this->generateDefaultShowView($name, $fields, $viewPath, $resourceName, $routePrefix);
        
        // Generate layout if it doesn't exist
        $this->generateLayout();
    }

    /**
     * Generate default form view.
     *
     * @param string $name
     * @param array $fields
     * @param string $viewPath
     * @return void
     */
    protected function generateDefaultFormView(string $name, array $fields, string $viewPath): void
    {
        $path = resource_path('views/' . $viewPath . '/_form.blade.php');
        
        if (!$this->shouldReplaceFile($path, "_form view exists. Replace it? [y/n]")) {
            return;
        }

        $this->writeFile($path, $this->getStubContent('crud.views.default._form'));
        $this->line('  ✓ Default _form view created');
    }

    /**
     * Generate default index view.
     *
     * @param string $name
     * @param array $fields
     * @param string $viewPath
     * @param string $resourceName
     * @param string $routePrefix
     * @return void
     */
    protected function generateDefaultIndexView(string $name, array $fields, string $viewPath, string $resourceName, string $routePrefix): void
    {
        $stub = $this->getStubContent('crud.views.default.index');
        $stub = str_replace('{{ $resourceName }}', $resourceName, $stub);
        $stub = str_replace('{{ $routePrefix }}', $routePrefix, $stub);
        
        $path = resource_path('views/' . $viewPath . '/index.blade.php');
        
        if (!$this->shouldReplaceFile($path, "Index view exists. Replace it? [y/n]")) {
            return;
        }

        $this->writeFile($path, $stub);
        $this->line('  ✓ Default index view created');
    }

    /**
     * Generate default create view.
     *
     * @param string $name
     * @param string $viewPath
     * @param string $resourceName
     * @param string $routePrefix
     * @return void
     */
    protected function generateDefaultCreateView(string $name, string $viewPath, string $resourceName, string $routePrefix): void
    {
        $stub = $this->getStubContent('crud.views.default.create');
        $stub = str_replace('{{ $resourceName }}', 'New ' . ucfirst($name), $stub);
        $stub = str_replace('{{ $routePrefix }}', $routePrefix, $stub);
        
        $path = resource_path('views/' . $viewPath . '/create.blade.php');
        
        if (!$this->shouldReplaceFile($path, "Create view exists. Replace it? [y/n]")) {
            return;
        }

        $this->writeFile($path, $stub);
        $this->line('  ✓ Default create view created');
    }

    /**
     * Generate default edit view.
     *
     * @param string $name
     * @param string $viewPath
     * @param string $resourceName
     * @param string $routePrefix
     * @return void
     */
    protected function generateDefaultEditView(string $name, string $viewPath, string $resourceName, string $routePrefix): void
    {
        $stub = $this->getStubContent('crud.views.default.edit');
        $stub = str_replace('{{ $resourceName }}', ucfirst($name), $stub);
        $stub = str_replace('{{ $routePrefix }}', $routePrefix, $stub);
        
        $path = resource_path('views/' . $viewPath . '/edit.blade.php');
        
        if (!$this->shouldReplaceFile($path, "Edit view exists. Replace it? [y/n]")) {
            return;
        }

        $this->writeFile($path, $stub);
        $this->line('  ✓ Default edit view created');
    }

    /**
     * Generate default show view.
     *
     * @param string $name
     * @param array $fields
     * @param string $viewPath
     * @param string $resourceName
     * @param string $routePrefix
     * @return void
     */
    protected function generateDefaultShowView(string $name, array $fields, string $viewPath, string $resourceName, string $routePrefix): void
    {
        $stub = $this->getStubContent('crud.views.default.show');
        $stub = str_replace('{{ $resourceName }}', ucfirst($name), $stub);
        $stub = str_replace('{{ $routePrefix }}', $routePrefix, $stub);
        
        $path = resource_path('views/' . $viewPath . '/show.blade.php');
        
        if (!$this->shouldReplaceFile($path, "Show view exists. Replace it? [y/n]")) {
            return;
        }

        $this->writeFile($path, $stub);
        $this->line('  ✓ Default show view created');
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

