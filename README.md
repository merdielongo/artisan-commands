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

## Features

✅ **Modern PHP 8.1+** - Uses strict types and modern PHP features  
✅ **Laravel 9, 10 & 11 Support** - Compatible with latest Laravel versions  
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
