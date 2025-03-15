## MVC Generator

<p align="center">
  <strong>MVC Generator</strong> is a powerful package designed to streamline the creation of new modules with full CRUD functionality and generate corresponding views. This tool is an invaluable asset for accelerating your Laravel projects.
  
  Crafted specifically for [arwp/main-master](https://packagist.org/packages/arwp/main-master), its code structure is optimized for seamless integration.
</p>

<p align="center">
  Made with ❤️ by [ARWP](https://github.com/arwahyu01)
</p>

---

## Requirements

- **Use This Package:** [arwp/main-master](https://packagist.org/packages/arwp/main-master)
- **Laravel:** 11.0 or higher
- **PHP:** 8.2 or higher

## Features

- **Module Generation:**  
  Use the command `php artisan make:mvc [model name]` to generate a new module that includes:
  - ✅ **Controller:** Integrated with complete CRUD functionality.
  - ✅ **Model:** Configured with fillable fields and defined relationships.
  - ✅ **Migration:** Automatically creates the required database tables and relationships.
  - ✅ **Views:** Pre-built views supporting CRUD operations.
  - ✅ **Route:** A dedicated route for module operations.

- **Module Deletion:**  
  - Run `php artisan delete:mvc [name]` to delete MVC files one by one (with confirmation prompts).
  - Run `php artisan delete:mvc [name] --all` to remove all related files and database tables.

## Installation

Install the package via Composer:

```bash
composer require arwp/mvc
```

## Creating a New Module

To create a new module, execute:

```bash
php artisan make:mvc [model name]
```

**Examples:**

- **Standard Module Creation:**

  ```bash
  php artisan make:mvc User
  ```

- **Customized Component Generation:**

  Generate only specific components (e.g., model and view) by using flags:
  
  ```bash
  php artisan make:mvc User mv  # 'm' for model, 'v' for view, 'c' for controller, 't' for migration, 'r' for route
  ```

- **Single Component Creation:**

  For example, to generate only views:

  ```bash
  php artisan make:mvc User view
  ```

## Deleting a Module

To remove a module along with its associated files and database tables, run:

```bash
php artisan delete:mvc [model name]
```

## Setup and Configuration

1. **Register the Service Provider:**  
   Add the service provider in your `config/app.php`:

   ```php
   'providers' => [
       // ...
       Arwp\Mvc\MvcServiceProvider::class,
       // ...
   ],
   ```

2. **Publish the Package Resources:**  
   Publish the necessary resource files by running:

   ```bash
   php artisan vendor:publish --provider="Arwp\Mvc\MvcServiceProvider"
   ```

   This command will publish:
   - `config/mvc.php`
   - `routes/mvc-route.php`
   - `Console/Commands/createMvc.php`
   - `Console/Commands/deleteMvc.php`

3. **Configure Routing:**  
   Update your `RouteServiceProvider.php` to include the MVC routes. For example:

   ```php
   public function boot()
   {
       // ...
       Route::middleware(['web', 'auth', 'backend'])
           ->namespace('App\Http\Controllers\Backend')
           ->group(base_path('routes/mvc-route.php'));
       // ...
   }
   ```

4. **Customize Paths:**  
   In the published `config/mvc.php` file, adjust the paths to suit your project structure:

   ```php
   return [
       'path_controller' => 'app/Http/Controllers/Backend', // Controller folder path
       'path_model'      => 'app/Models',                     // Model folder path
       'path_view'       => 'views/backend',                  // View folder path (e.g., views/backend or views/frontend)
       'path_route'      => 'routes/mvc-route.php',           // Route file path (default: routes/mvc-route.php)
       'route_prefix'    => '',                               // Optional route prefix (e.g., backend, admin)
   ];
   ```

   To modify the default route file, simply update the `path_route` setting accordingly:

   ```php
   return [
       // ...
       'path_route' => 'routes/web.php', // Change this to your desired route file path
       // ...
   ];
   ```

   Lastly, ensure your designated route file (e.g., `routes/web.php`) contains the following marker:

   ```php
   //{{route replacer}} DON'T REMOVE THIS LINE
   ```

## License

MVC Generator is released under the [MIT License](LICENSE).
---

MVC Generator is designed to enhance the efficiency and simplicity of your development workflow. If you find this project valuable, 
your support with a star ⭐️ is greatly appreciated. Thank you for your contribution and happy coding! 🚀