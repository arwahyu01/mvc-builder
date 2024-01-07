<?php

namespace Arwp\Mvc\app;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

trait ControllerBuilder
{
    /**
     * Build and create a controller for the specified model.
     */
    public function controllerBuilder(): void
    {
        // Build the full path to the controller file
        $path = App::basePath(config('mvc.path_controller')) . '/' . $this->model_name;
        $path_controller = $path . '/' . $this->model_name . 'Controller.php';

        // Create the directory for the controller
        $this->createDirectory($path);

        // Copy the controller stub file to the new controller file
        File::copy($this->path_stub . '/controller/controller.stub', $path_controller);

        // Check if the controller file was successfully created
        if (File::exists($path_controller)) {
            $this->updateControllerFile($path_controller);

            $this->info('Controller created successfully');
        } else {
            $this->info('Controller failed to create');
        }
    }

    /**
     * Update the content of the controller file.
     *
     * @param string $path_controller
     */
    private function updateControllerFile(string $path_controller): void
    {
        // Read the content of the controller file
        $file_controller = File::get($path_controller);

        // Replace placeholders in the controller file with actual values
        $replaced = Str::replace('{{ namespace }}', $this->getControllerNamespace(), $file_controller);
        $replaced = Str::replace('{{ class }}', $this->model_name . 'Controller', $replaced);
        $replaced = Str::replace('{{ $validation }}', $this->createValidation(), $replaced);
        $replaced = Str::replace('$data=$this->model::all();', $this->createDataRelation(), $replaced);

        // Write the updated content back to the controller file
        File::put($path_controller, $replaced);
    }

    /**
     * Get the namespace for the controller.
     *
     * @return string
     */
    private function getControllerNamespace(): string
    {
        return Str::ucfirst(Str::replace('/', '\\', config('mvc.path_controller'))) . '\\' . $this->model_name;
    }

    /**
     * Create validation rules based on the specified fields.
     *
     * @return string
     */
    private function createValidation(): string
    {
        // Get validation rules for each field
        $validationRules = collect($this->fields)
            ->filter(fn($field) => $this->shouldIncludeValidationRule($field))
            ->map(function ($field) {
                if (Str::endsWith($field['field'], '_id')) {
                    // Check if the related model exists
                    $model_name = Str::ucfirst(Str::camel(Str::replace('_id', '', $field['field'])));
                    $target_model = App::basePath(config('mvc.path_model')) . '/' . $model_name . '.php';
                    if (File::exists($target_model)) {
                        return "'" . $field['field'] . "' => 'required|exists:" . Str::plural(Str::snake($model_name)) . ",id',";
                    }
                }
                return "'" . $field['field'] . "' => 'required',";
            })
            ->implode(PHP_EOL . "\t\t\t");

        // Remove the trailing newline character
        return $validationRules;
    }

    /**
     * Create the data retrieval method.
     *
     * @return string
     */
    private function createDataRelation(): string
    {
        $path_model = App::basePath(config('mvc.path_model'));

        // Filter and map the fields to get the related models
        $relations = collect($this->fields)
            ->filter(fn($field) => $this->shouldIncludeValidationRule($field) && $this->isRelatedModelExists($field, $path_model))
            ->map(fn($field) => Str::replace('_id', '', $field['field']))
            ->implode("','");

        // If there are relations, modify the data retrieval query
        $dataQuery = '$data=$this->model::all();';
        $withRelationsQuery = $relations !== '' ? '$data = $this->model::with(\'' . $relations . '\');' : $dataQuery;

        return Str::replace($dataQuery, $withRelationsQuery, $dataQuery);
    }


    /**
     * Check if the related model exists.
     *
     * @param array $field
     * @param string $path_model
     * @return bool
     */
    private function isRelatedModelExists(array $field, string $path_model): bool
    {
        $model_name = Str::ucfirst(Str::camel(Str::replace('_id', '', $field['field'])));
        $target_model = $path_model . '/' . $model_name . '.php';

        return File::exists($target_model);
    }

    /**
     * Check if a validation rule should be included for the field.
     *
     * @param array $field
     * @return bool
     */
    private function shouldIncludeValidationRule(array $field): bool
    {
        return $field['type'] != 'uuidMorphs' && $field['view'] != "No Input Elements" && $field['field'] != 'id';
    }

}