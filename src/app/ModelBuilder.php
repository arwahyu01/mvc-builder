<?php

namespace Arwp\Mvc\app;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

trait ModelBuilder
{
    /**
     * Generate and create a model for the specified entity.
     */
    public function modelBuilder()
    {
        $this->info('Creating model...');

        // Build the path to the model file
        $path_model = App::basePath(config('mvc.path_model'));
        $target_model = $path_model . '/' . $this->model_name . '.php';

        // Copy the model stub file to the target model file
        File::copy($this->path_stub . '/model/model.stub', $target_model);

        // Check if the model file was successfully created
        if (File::exists($target_model)) {
            // Read the content of the model file
            $file = File::get($target_model);

            // Replace placeholders in the model file with actual values
            $replaced = Str::replace('{{ namespace }}', Str::ucfirst(Str::replace('/', '\\', config('mvc.path_model'))), $file);
            $replaced = Str::replace('{{ class }}', $this->model_name, $replaced);
            $replaced = Str::replace('{{ fillable }}', $this->createModelFields(), $replaced);
            $replaced = Str::replace('{{ casts }}', $this->createCasts(), $replaced);
            $replaced = Str::replace('{{ table }}', Str::snake(Str::plural($this->model_name), '-'), $replaced);
            $replaced = Str::replaceLast('}', $this->createRelations() . '}', $replaced);

            // Write the updated content back to the model file
            File::put($target_model, $replaced);

            $this->info('Model created successfully');
        } else {
            $this->info('Model failed to create, please try again');
        }
    }

    /**
     * Generate a string containing fillable fields for the model.
     *
     * @return string
     */
    private function createModelFields(): string
    {
        $fields = '';

        foreach ($this->fields as $field) {
            if ($field['type'] != 'uuidMorphs' && !Str::contains($field['field'], '_morph')) {
                $fields .= "'" . $field['field'] . "',";
            }
        }

        return Str::beforeLast($fields, ',');
    }

    /**
     * Generate a string containing model relations based on the specified fields.
     *
     * @return string
     */
    private function createRelations(): string
    {
        $function = '';

        foreach ($this->fields as $field) {
            if (Str::contains($field['field'], '_id') && $field['relation']) {
                $name = Str::replace('_id', '', $field['field']);

                // Determine the target relation
                $target_relation = ($field['field'] == 'parent_id')
                    ? "(" . $this->model_name . "::class);"
                    : "(" . Str::ucfirst(Str::camel($name)) . "::class);";

                // Build the relation function
                $function .= "\t" . "public function " . Str::lower($name) . "()" . PHP_EOL . "\t" . "{" . PHP_EOL . "\t\t" . "return \$this->" . $field['type_relation'] . $target_relation . PHP_EOL . "\t" . "}" . PHP_EOL;
            }
        }

        return PHP_EOL . $function;
    }

    /*
     * Generate a cast string for the model.
     */
    private function createCasts(): string
    {
        $casts = '';
        foreach ($this->fields as $field) {
            if ($field['type'] == 'json' || $field['type'] == 'jsonb') {
                $casts .= "'" . $field['field'] . "' => 'array',";
            }
        }

        return Str::beforeLast($casts, ',');
    }

}

