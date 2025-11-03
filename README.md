# Kernel243/Artisan

A Laravel package that provides a set of custom artisan commands to speed up your development workflow.

## Installation

Install the package via Composer:

```bash
composer require kernel243/artisan
```

The service provider will be automatically registered. For Laravel versions prior to 5.5, you may need to manually register the provider in your `config/app.php`:

```php
'providers' => [
    // ...
    Davinet\ArtisanCommand\ArtisanCommandServiceProvider::class,
],
```

## Requirements

- PHP >= 8.1
- Laravel >= 9.0

## Available Commands

All commands support the following options:
- `--force`: Overwrite existing files without confirmation
- `--dry-run`: Preview what would be created without actually creating files

### 1. View Command

Create Blade view files with optional layout support.

**Generate an empty view:**
```bash
php artisan make:view folder.subfolder.view
```

**Generate a view with a layout:**
```bash
php artisan make:view folder.subfolder.view --layout=app
```

**Force overwrite existing view:**
```bash
php artisan make:view folder.subfolder.view --force
```

### 2. Repository Command

Generate repository classes following the Repository pattern.

**Generate an empty repository:**
```bash
php artisan make:repository UserRepository
```

**Generate a repository with a model:**
```bash
php artisan make:repository UserRepository --model=User
```

Or with full namespace:
```bash
php artisan make:repository UserRepository --model=App\Models\User
```

**Preview without creating:**
```bash
php artisan make:repository UserRepository --dry-run
```

### 3. Service Command

Create service classes for your business logic.

```bash
php artisan make:service PayPalPaymentService
```

Service classes are created in `app/Services/` directory.

### 4. Language Command

Generate language files for Laravel's localization system.

**Generate a PHP language file:**
```bash
php artisan make:lang messages --locale=es
```

**Generate a JSON language file:**
```bash
php artisan make:lang --locale=es --json
```

### 5. Class Command

Create classes, traits, or interfaces with proper namespaces.

**Generate a class:**
```bash
php artisan make:class App\Handlers\UserHandlers
```

Or using dot notation as separator:
```bash
php artisan make:class App.Handlers.UserHandlers --separator=.
```

**Generate a trait:**
```bash
php artisan make:class App\Traits\MyTrait --kind=trait
```

**Generate an interface:**
```bash
php artisan make:class App\Contracts\IClassable --kind=interface
```

### 6. File Command

Generate generic files with any extension.

```bash
php artisan make:file folder.subfolder1.subfolder2.filename --ext=php
```

Or with other extensions:
```bash
php artisan make:file config.app --ext=json
php artisan make:file scripts.helper --ext=js
```

### 7. CRUD Command

Generate complete CRUD resources with Model, Controller, Repository, Service, and Blade views.

**Generate a CRUD without fields (you'll add them manually):**
```bash
php artisan make:crud Product
```

**Generate a CRUD with fields:**
```bash
php artisan make:crud Product --fields="title:string,description:text,price:decimal,is_active:boolean"
```

**Force overwrite existing CRUD files:**
```bash
php artisan make:crud Product --force
```

**What it generates:**
- ✅ Model (`app/Models/Product.php`)
- ✅ Repository (`app/Repositories/ProductRepository.php`)
- ✅ Service (`app/Services/ProductService.php`)
- ✅ Controller (`app/Http/Controllers/ProductController.php`)
- ✅ Views (index, create, edit, show) with Tailwind CSS styling
- ✅ Routes in `routes/web.php` - **automatically added**
- ✅ Layout template with Tailwind CSS (`resources/views/layouts/app.blade.php`)
- ✅ Tailwind CSS configuration (`tailwind.config.js`)

**Supported field types:**
- `string` or `text` or `varchar` - Text inputs
- `textarea` or `text` - Textarea fields
- `email` - Email inputs
- `boolean` or `tinyint` - Checkbox fields
- Any other type defaults to text input

### 8. Resource CRUD Command

Generate complete CRUD resources with Resource classes (Laravel Filament-inspired pattern).

**Generate a Resource CRUD:**
```bash
php artisan make:resource-crud Product
```

**Generate a Resource CRUD with fields:**
```bash
php artisan make:resource-crud Product --fields="title:string,description:text,price:decimal,is_active:boolean"
```

**Force overwrite existing files:**
```bash
php artisan make:resource-crud Product --force
```

**What it generates:**
- ✅ Model (`app/Models/Product.php`)
- ✅ Repository (`app/Repositories/ProductRepository.php`)
- ✅ Service (`app/Services/ProductService.php`)
- ✅ Controller (`app/Http/Controllers/ProductController.php`) - **injects Resource class**
- ✅ Resource class (`app/Resources/ProductResource.php`) - **Filament-inspired pattern**
- ✅ Base classes (`app/Resources/Resource.php`, `app/Builders/Form.php`, `app/Builders/Table.php`)
- ✅ Default views with Tailwind CSS (`resources/views/resources/crud/*.blade.php`) - **uses Resource class**
- ✅ Routes in `routes/web.php` - **automatically added**
- ✅ Layout template with Tailwind CSS (`resources/views/layouts/app.blade.php`)
- ✅ Tailwind CSS configuration (`tailwind.config.js`)

**Resource Class Pattern:**
The Resource CRUD command generates a Resource class inspired by Laravel Filament that defines:
- `form($form)` - Form field definitions
- `table($table)` - Table column definitions (Yajra DataTables compatible)
- `actions()` - Custom actions
- `rules()` - Validation rules
- `getFillable()` - Model fillable fields

**Supported field types:**
- `string` or `text` or `varchar` - Text inputs
- `textarea` or `text` - Textarea fields
- `email` - Email inputs
- `boolean` or `tinyint` - Checkbox fields
- `decimal`, `integer`, `bigint` - Number fields
- `datetime`, `date`, `timestamp` - Date/time fields
- Any other type defaults to text input

**Example Resource class generated:**
```php
class ProductResource extends Resource
{
    protected $model = App\Models\Product::class;
    
    public function form($form)
    {
        return $form->schema([
            Form::text('title', 'Title'),
            Form::textarea('description', 'Description'),
            Form::number('price', 'Price'),
            Form::checkbox('is_active', 'Is Active'),
        ]);
    }
    
    public function table($table)
    {
        return $table->columns([
            Table::text('id', 'ID'),
            Table::text('title', 'Title'),
            Table::number('price', 'Price'),
            Table::boolean('is_active', 'Active'),
            Table::actions(),
        ]);
    }
}
```

## Features

✅ **Modern PHP 8.1+** - Uses strict types and modern PHP features  
✅ **Laravel 9, 10 & 11 Support** - Compatible with latest Laravel versions  
✅ **Complete CRUD Generation** - Generate full CRUD with views, controller, model, repository & service  
✅ **Filament-Inspired Pattern** - Resource classes with form() and table() methods  
✅ **Yajra DataTables** - Table builder compatible with Yajra DataTables  
✅ **Tailwind CSS** - Beautiful, modern UI with Tailwind CSS included  
✅ **Force Overwrite** - Skip confirmations with `--force` flag  
✅ **Dry Run Mode** - Preview changes without modifying files  
✅ **Better Error Handling** - Clear error messages and exception handling  
✅ **Type Safety** - Full type hints and return types  
✅ **Security** - Path traversal protection and input validation  

## Contributing

Pull requests are welcome! For major changes, please open an issue first to discuss what you would like to change.

## License

This package is open-sourced software licensed under the [MIT license](https://choosealicense.com/licenses/mit/).

## Author

**Merdi Elongo**
- Email: merdielongo9@gmail.com
