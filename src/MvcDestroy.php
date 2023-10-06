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
    protected $signature='delete:mvc {module}';
    protected $description='Delete MVC, Model, Controller, View, Route';
    private string $model;

    public function handle(): bool
    {
        $this->model=$this->argument('module');
        $this->build();
        return true;
    }

    public function build(): void
    {
        if ($this->confirm('Are you sure you want to delete the MVC '.$this->model.'?')) {
            $this->choice('What do you want to delete?', ['Model', 'View', 'Controller', 'Migration', 'Route', 'All'], 5);
            if ($this->confirm('Delete the Model file?')) {
                $this->deleteModel();
            }
            if ($this->confirm('Delete the View file?')) {
                $this->deleteView();
            }
            if ($this->confirm('Delete the Controller file?')) {
                $this->deleteController();
            }
            if ($this->confirm('Delete the migration file?')) {
                $this->deleteMigration();
            }
            if ($this->confirm('delete Route of this MVC?')) {
                $this->deleteRoute();
            }
            $this->call('optimize:clear');
            $this->info('Cache cleared successfully');
            $this->info('MVC '.$this->model.' successfully deleted');
        } else {
            $this->info('Your data is safe');
        }
    }

    private function deleteMigration(): void
    {
        $database=File::files(database_path('migrations'));
        $filename=Str::snake(Str::plural($this->model));
        collect($database)->map(function ($data) use ($filename) {
            if (Str::contains($data, 'create_'.$filename.'_table.php')) {
                File::delete($data);
                if (Schema::hasTable($filename)) {
                    DB::table('migrations')->where('migration', 'like', '%create_'.$filename.'_table%')->delete();
                    Schema::disableForeignKeyConstraints();
                    Schema::drop($filename);
                    Schema::enableForeignKeyConstraints();
                    $this->info('Your table has been successfully deleted from the database and migration file');
                }
            }
        });
    }

    private function deleteModel(): void
    {
        $path=Str::replace('/', '\\', config('mvc.path_model')).'\\'.$this->model.'.php';
        File::delete(App::basePath($path));
        if (!File::exists($path)) {
            $this->info('Model file successfully deleted');
        }
    }

    private function deleteView(): void
    {
        $path=resource_path(config('mvc.path_view').'/'.Str::snake($this->model, '-'));
        File::deleteDirectory($path);
        if (!File::exists($path)) {
            $this->info('View file successfully deleted');
        }
    }

    private function deleteController(): void
    {
        $dir=App::basePath(config('mvc.path_controller').'/'.$this->model);
        File::deleteDirectory($dir);
        if (!File::exists($dir.'/'.$this->model.'Controller'.'.php')) {
            $this->info('Controller file successfully deleted');
        }
    }

    private function deleteRoute(): void
    {
        $lowerName=Str::lower(Str::snake($this->model, '-'));
        $route=File::get(App::basePath(config('mvc.path_route')));
        if (Str::contains($route, '//'.$lowerName)) {
            $star=Str::of($route)->before('//'.$lowerName);
            $end=Str::of($route)->after('//end-'.$lowerName);
            $route=$star.$end;
            $route=preg_replace('/^\h*\v+/m', '', $route);
            File::put(App::basePath(config('mvc.path_route')), $route);
            $this->info('Route file successfully deleted'.PHP_EOL);
        }
    }
}
