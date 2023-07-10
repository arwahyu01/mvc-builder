<?php

namespace Arwp\Mvc;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MvcDestroy extends Command
{
    protected $signature = 'delete:mvc {module}';
    protected $description = 'Delete MVC, Model, Controller, View, Route';
    private $model;

    public function handle(): bool
    {
        $this->model = $this->argument('module');
        $this->build();
        return TRUE;
    }

    public function build(): void
    {
        if ($this->confirm('Are you sure you want to delete the data ' . $this->model . '?')) {
            if ($this->confirm('Do you want to delete the migration file?')) {
                File::delete(App::basePath(Str::ucfirst(config('mvc.path_model')) . $this->model . '.php'));
                $this->info('Model file successfully deleted');
                $database = File::files(database_path('migrations'));
                collect($database)->map(function ($data) {
                    if (Str::contains($data, 'create_' . Str::snake(Str::plural($this->model)) . '_table.php')) {
                        File::delete($data);
                        if (Schema::hasTable(Str::snake(Str::plural($this->model)))) {
                            DB::table('migrations')->where('migration', 'like', '%create_' . Str::snake(Str::plural($this->model)) . '_table%')->delete();
                            Schema::disableForeignKeyConstraints();
                            Schema::drop(Str::snake(Str::plural($this->model)));
                            Schema::enableForeignKeyConstraints();
                            $this->info('Your table has been successfully deleted from the database and migration file');
                        }
                    }
                });
            } else {
                File::delete(App::basePath(Str::ucfirst(config('mvc.path_model')) . $this->model . '.php'));
                if (!File::exists(App::basePath(Str::ucfirst(config('mvc.path_model')) . $this->model . '.php'))) {
                    $this->info('Model file successfully deleted');
                }
            }
            File::deleteDirectory(App::basePath(config('mvc.path_controller') . '/' . $this->model));
            if (!File::exists(App::basePath(config('mvc.path_controller') . '/' . $this->model . '/' . $this->model . 'Controller' . '.php'))) {
                $this->info('Controller file successfully deleted');
            }
            $target = resource_path(config('mvc.path_view') . '/' . Str::snake($this->model, '-'));
            File::deleteDirectory($target);
            if (!File::exists($target)) {
                $this->info('View file successfully deleted');
            }

            // route delete
            $lowerName = Str::lower(Str::snake($this->model, '-'));
            $route = File::get(App::basePath(config('mvc.path_route')));
            if (Str::contains($route, '//' . $lowerName)) {
                $star = Str::of($route)->before('//' . $lowerName);
                $end = Str::of($route)->after('//end-' . $lowerName);
                $route = $star . $end;
                $route = preg_replace('/^\h*\v+/m', '', $route);
                File::put(App::basePath(config('mvc.path_route')), $route);
                $this->info('Route file successfully deleted' . PHP_EOL);
            }

            $this->call('optimize:clear');
            $this->info('Cache cleared successfully');
            $this->info('MVC ' . $this->model . ' successfully deleted');
        } else {
            $this->info('Your data is safe');
        }
    }
}

