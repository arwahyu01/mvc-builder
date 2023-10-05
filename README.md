<h3 style="text-align: center"> MVC Generator V1.0.1 </h3>
<p style="text-align: center">
MVC Generator is a package that can help you to create a new module with CRUD function and also create a view for that module. This package is very useful for save your time.
<br>
This library is specifically designed for <a href="https://packagist.org/packages/arwp/main-master" title="Laravel">arwp/main-master</a>  
, and the code structure within this library is tailored to support that template. 
</p>
<p style="text-align: center">
Made with ❤️ by <a href="https://github.com/arwahyu01" title="Ar. Wahyu Pradana">ARWP</a>
</p>

## Requirements

- Laravel 10.0 or higher
- PHP 8.1 or higher

## Features
- Use `php artisan make:mvc [name of model]` to create a new module
  - [x] Controller (with CRUD function)
  - [x] Model (with fillable and relation)
  - [x] Migration (with table and relation)
  - [x] views (with CRUD function)
  - [x] new route (with CRUD function)
- Use `php artisan delete:mvc [name]` to delete a module (delete all file and table in database)

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
If you want to change the default "PATH ROUTE," you can do so in config/mvc.php.
```
return [
    ...
    'path_route' => 'routes/web.php', // change this to your route file
    ...
];
```
Copy the code below to your route file (e.g: routes/web.php)
```
//{{route replacer}} DON'T REMOVE THIS LINE
```

## License
MVC Generator is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

I hope this project can help you to make your project faster and easier to develop :)

If you like this project, please give me a star ⭐️, thank you very much.

Buy me a coffee ☕️ : [click here](https://trakteer.id/arwp) thank you for your support.
