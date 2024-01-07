<?php

namespace Arwp\Mvc;

use Arwp\Mvc\app\ControllerBuilder;
use Arwp\Mvc\app\MigrationBuilder;
use Arwp\Mvc\app\ModelBuilder;
use Arwp\Mvc\app\RouteBuilder;
use Arwp\Mvc\app\ViewBuilder;
use Arwp\Mvc\utilities\StringUtil;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MvcBuilder extends GeneratorCommand
{
    use ModelBuilder, MigrationBuilder, ViewBuilder, ControllerBuilder, RouteBuilder, StringUtil;

    protected $signature = 'make:mvc {model} {mvc? : MVC Name}';
    protected $description = 'Create a new MVC, Model, View, Controller, Route, Migration, Factory';
    private string $path_stub = __DIR__ . '/resources/stub';
    private string $model_name;
    private bool $model = true;
    private bool $view = true;
    private bool $controller = true;
    private bool $route = true;
    private bool $migration = true;
    private array $fields = [];
    private $typeInput;
    private $options;

    protected function getStub(): string
    {
        return '';
    }

    public function handle(): bool
    {
        $this->model_name = Str::ucfirst(Str::camel(StringUtil::cleanString($this->argument('model'), ' ')));
        $prefix = ['migration', 'model', 'view', 'controller', 'route']; // prefix for mvc argument
        if ($mvc_request = Str::lower($this->argument('mvc') ?? '')) {
            if (in_array($mvc_request, $prefix)) {
                $this->model = Str::contains($mvc_request, 'model');
                $this->view = Str::contains($mvc_request, 'view');
                $this->controller = Str::contains($mvc_request, 'controller');
                $this->route = Str::contains($mvc_request, 'route');
                $this->migration = Str::contains($mvc_request, 'migration');
            } else {
                $this->model = ($mvc_request == 'm'); // m for model
                $this->view = ($mvc_request == 'v'); // v for view
                $this->controller = ($mvc_request == 'c'); // c for controller
                $this->route = ($mvc_request == 'r'); // r for route
                $this->migration = ($mvc_request == 't'); // t for migration (table)
            }
        }

        // Check if the model, view, or controller already exists
        $check_m = File::exists(App::basePath(config('mvc.path_model')) . '/' . $this->model_name . '.php');
        $check_c = File::exists(App::basePath(config('mvc.path_controller')) . '/' . $this->model_name . 'Controller.php');
        $check_v = File::exists(resource_path(config('mvc.path_view') . '/' . Str::snake($this->model_name, '-')));

        if ($check_m || $check_v || $check_c) {
            // Prompt user to delete and recreate if already exists
            if ($this->confirm('Do you want to delete it, and create a new one ?')) {
                $this->call('delete:mvc', ['module' => $this->model_name]);
                $this->build(); // Proceed to build
            } else {
                $this->info('Finished, nothing changed !');
            }
        } else {
            // If not exists, proceed with the build process
            $this->build();
        }
        return true;
    }

    public function build(): void
    {
        $this->info('Initiating the process to create MVC for ' . $this->model_name);
        $selectedOption = Str::lower($this->argument('mvc') ?? '');

        // Check if the selected option is for route creation
        if ($selectedOption == 'route' || $selectedOption == 'r') {
            $this->routeBuilder();
        } else {
            // Proceed with collecting model fields if a specific option is not chosen
            $this->inputModelFields();

            // Check for the selected option and execute the corresponding step
            if ($selectedOption == 'migration' || $selectedOption == 't') {
                $this->migrationBuilder();
                $this->modelBuilder();
            } elseif ($selectedOption == 'model' || $selectedOption == 'm') {
                $this->modelBuilder();
                $this->migrationBuilder();
            } elseif ($selectedOption == 'view' || $selectedOption == 'v') {
                $this->viewBuilder();
            } elseif ($selectedOption == 'controller' || $selectedOption == 'c') {
                $this->controllerBuilder();
            } else {
                // If no specific option is selected, execute all creation steps based on user preferences
                if ($this->migration) {
                    $this->migrationBuilder();
                }
                if ($this->model) {
                    $this->modelBuilder();
                }
                if ($this->view) {
                    $this->viewBuilder();
                }
                if ($this->controller) {
                    $this->controllerBuilder();
                }
                if ($this->route) {
                    $this->routeBuilder();
                }
            }
        }

        $this->info('MVC ' . $this->model_name . ' successfully created');
        $this->call('optimize:clear');
        $this->info('Process completed');
        $this->info('Please run "php artisan migrate" to migrate the database');
    }

    /**
     * Recursive function to input model fields until the user pushes enter to skip.
     */
    private function inputModelFields(): void
    {
        $field = $this->ask('Type a field name (push enter to skip)');

        if ($field != '') {
            $this->processField($field);
            $this->displayFieldInfo($field);
            $this->processViewInput();
            $this->storeFieldInfo();
            $this->inputModelFields(); // recursive function to input fields until the user pushes enter to skip
        }
    }

    /**
     * Process the user input for a field.
     *
     * @param string $field The field name.
     */
    private function processField($field): void
    {
        $field = Str::lower(StringUtil::cleanString($field, '_'));
        $dataType = $this->chooseDataTypes($this->dataTypes($field), $field);

        if ($this->model) {
            $relation = $this->confirm('This field has a relation ? ' . $field);

            if ($relation) {
                $model = Str::ucfirst(Str::camel(Str::replace('_id', '', $field)));

                if (File::exists(App::basePath(config('mvc.path_model') . '/' . $model . '.php'))) {
                    $this->type_relation = $this->choice('Choose Relation Type', ['belongsTo', 'hasMany', 'hasOne', 'belongsToMany'], 0);
                } else {
                    $this->warn('Model ' . $model . ' not found, please create it first !');
                    $this->inputModelFields();
                }
            }

            $this->fieldInfo = [
                'field' => $field,
                'type' => $dataType,
                'relation' => $relation ?? false,
                'type_relation' => $this->type_relation ?? null,
            ];
        }
    }

    /**
     * Display information about the processed field.
     *
     * @param string $field The field name.
     */
    private function displayFieldInfo($field): void
    {
        if ($this->model) {
            $this->info('Field : "' . $field . '", Type : "' . $this->fieldInfo['type'] . '", Relation : "' . ($this->fieldInfo['relation'] ? 'Yes, ' . ($this->fieldInfo['type_relation'] ?? '') : 'No') . '"');
        }
    }

    /**
     * Process the user input for the view element.
     */
    private function processViewInput(): void
    {
        if ($this->view) {
            $choices = collect(config('mvc.view'))->keys()->prepend('No Input Elements')->toArray();
            $this->typeInput = $this->choice('Choose Input Element', $choices, 0);

            if ($this->typeInput == 'radio') {
                $this->options = $this->askForOptions();
            }

            $this->info('Input Element : "' . $this->typeInput . '"');
        }
    }

    /**
     * Store the field information for later use.
     */
    private function storeFieldInfo(): void
    {
        $this->fieldInfo['view'] = $this->typeInput ?? null;
        $this->fieldInfo['options'] = $this->options ?? [];
        $this->fields[] = $this->fieldInfo;
    }


    /**
     * Choose data types for a field.
     *
     * @param string $dataType The user-input data type.
     * @param string $field The field name.
     *
     * @return string The chosen data type.
     */
    private function chooseDataTypes($dataType, $field): string
    {
        $data_type = Str::camel(StringUtil::cleanString($dataType, ' '));
        $type = collect(config('mvc.column_types'))->first(fn($item) => Str::lower($item) == Str::lower($data_type));
        if (is_null($type)) {
            $this->error('Your input "' . $dataType . '" for field "' . $field . '" is not found in the list');
            return $this->chooseDataTypes($this->dataTypes($field), $field);
        }
        return $type;
    }

    /**
     * Get the data type for a field.
     *
     * @param string $field The field name.
     *
     * @return string The data type.
     */
    private function dataTypes($field): string
    {
        $type = $this->ask('Type data type for field ' . $field . ' / type "list" to see list of data type');
        // show list of data type if user input "list"
        if (Str::upper($type) == 'LIST') {
            $columnType = config('mvc.column_types');
            $chunks = array_chunk($columnType, 10);
            foreach ($chunks as $chunk) {
                $type = $this->choice('Choose Data Type for ' . $field, collect($chunk)->push('Other')->toArray());
                if ($type != 'Other') {
                    break;
                }
            }
        }
        return $type;
    }

    /**
     * Create a directory if it does not exist.
     *
     * @param string $path The path of the directory.
     */
    private function createDirectory(string $path): void
    {
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true, true);
        }
    }

    private function askForOptions()
    {
        $options = [];
        $option = $this->ask('Type option value {key:value:true/false} (push enter to skip)');
        while ($option != '') {
            $options[] = explode(':', $option);
            $option = $this->ask('Type option value {key:value:true/false} (push enter to skip)');
        }
        return $options;
    }
}
