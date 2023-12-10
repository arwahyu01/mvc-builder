<h3 style="text-align: center">MVC Generator V1.0.1</h3>
<p style="text-align: center">
  MVC Generator is a powerful package designed to streamline the creation of new modules with CRUD functionality and generate corresponding views. This tool is a time-saving asset for your projects.
  <br>
  This library is specifically crafted for <a href="https://packagist.org/packages/arwp/main-master" title="Laravel">arwp/main-master</a>, and its code structure is optimized to align seamlessly with that template.
</p>
<p style="text-align: center">
  Made with ❤️ by <a href="https://github.com/arwahyu01" title="Ar. Wahyu Pradana">ARWP</a>
</p>


## Requirements

- Laravel 10.0 or higher
- PHP 8.1 or higher

## Features
- Utilize `php artisan make:mvc [model name]` to create a new module.
  - [x] Controller (with CRUD functionality)
  - [x] Model (with fillable fields and relationships)
  - [x] Migration (with table creation and relationships)
  - [x] Views (with CRUD functionality)
  - [x] New route (with CRUD functionality)
- Use `php artisan delete:mvc [name]` to delete a module (delete mvc files one by one with confirmation).
- Use `php artisan delete:mvc [name] --all` to delete a module (delete all files and tables in the database).

## How to install
```bash
$ composer require arwp/mvc
```
### Create a new module
```bash
# run this command to create a new module
$ php artisan make:mvc [name of model]
# example 
$ php artisan make:mvc User 
# or custom command :
# e.g: php artisan make:mvc User mv to create model and view
$ php artisan make:mvc User mv #{m=model, v=view, c=controller, t=table/migration, r=route}
# or custom command :
$ php artisan make:mvc User view #['migration', 'model', 'view', 'controller', 'route']
```
### Delete a module (delete all file and table in database)
```bash
# run this command to delete a module
$ php artisan delete:mvc [name of model]
```
## Setup and Configuration :
add this code to your config/app.php
```
'providers' => [
    ...
    Arwp\Mvc\MvcServiceProvider::class,
    ...
]
```
you need to publish the resource file to your project
```bash
$ php artisan vendor:publish --provider="Arwp\Mvc\MvcServiceProvider"
  #publised file config/mvc.php
  #publised file routes/mvc-route.php
  #publised file Console/Commands/createMvc.php
  #publised file Console/Commands/deleteMvc.php
````
add this code to your routeServiceProvider.php
```
public function boot()
{
    ...
    Route::middleware(['web','auth','backend'])->namespace('App\Http\Controllers\Backend')->group(base_path('routes/mvc-route.php'));
    ...
}
```

open file config/mvc.php and change the key value to your path folder
```
return [
    'path_controller' => 'app/Http/Controllers/Backend', // this is path to controller folder
    'path_model' => 'app/Models', // this is path to model folder
    'path_view' => 'views/backend', // this is path to view folder (e.g: views/backend or views/frontend)
    'path_route' => 'routes/mvc-route.php', // path to route file (default: routes/mvc-route.php)
    'route_prefix' => '', // Customize with your "Prefix Route" (e.g: backend, admin, etc) (optional)
];
```
If you wish to modify the default "PATH ROUTE," you can make adjustments in the config/mvc.php file.
```
return [
    ...
    'path_route' => 'routes/web.php', // Change this to your desired route file path
    ...
];
```
Copy and paste the following code into your specified route file (e.g., routes/web.php):
```
//{{route replacer}} DON'T REMOVE THIS LINE
```

## License
MVC Generator is released as open-source software under the [MIT license](https://opensource.org/licenses/MIT).

This project is designed to enhance the efficiency and simplicity of your development process. I trust that this tool will prove valuable in accelerating your project development.

If you find this project beneficial, your support in the form of a star ⭐️ would be greatly appreciated. Thank you for your consideration and contribution.
