<?php

namespace App\Console\Commands;

use Arwp\Mvc\MvcBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class createMVC extends Command
{
    protected $signature = 'make:mvc {model}';
    protected $description = 'To make controller, model , view, migration, and route for a Backend Module';
    private string $model;
    private array $fields = [];

    public function handle(): bool
    {
        $this->model = Str::ucfirst(Str::camel($this->cleanString($this->argument('model'), ' ')));
        if (File::exists(App::basePath(config('mvc.path_controller')) . '/' . $this->model)) {
            $this->info('Oops, your module ' . $this->model . ' already exists !');
            $confirm = $this->confirm('Do you want to delete it, and create a new one ?');
            if ($confirm) {
                $this->call('delete:mvc', ['module' => $this->model]);
                $this->build();
            } else {
                $this->info('Finished, nothing changed !');
            }
        } else {
            $this->build();
        }
        return TRUE;
    }

    public function build(): void
    {
        $this->info('Preparing to create MVC for ' . $this->model);
        $this->inputModelFields();
        (new MvcBuilder())->createMvc($this->model, $this->fields);
        $this->info('MVC ' . $this->model . ' created successfully');
        $this->call('route:clear');
        $this->call('view:clear');
        $this->call('cache:clear');
        $this->info('Run "php artisan migrate" to migrate database');
        $this->info('Finished');
    }

    private function inputModelFields(): void
    {
        $field = $this->ask('Type field name (push enter to skip)');
        if (Str::lower($field) != '') {
            $field = Str::lower($this->cleanString($field, '_'));
            $dataType = $this->choiceDataTypes($this->dataTypes($field), $field);
            $relation = $this->confirm('This field has relation ? ' . $field);
            $this->info('Field : "' . $field . '", Type : "' . $dataType . '", Relation : "' . ($relation ? 'Yes' : 'No') . '"');
            $choice = collect(collect(config('template.view'))->keys())->prepend('No Input Elements')->toArray();
            $typeInput = $this->choice('Choose Input Element', $choice, 0);
            $this->info('Input Element : "' . $typeInput . '"');
            $this->fields[] = [
                'field' => $field, 'type' => $dataType, 'relation' => $relation, 'view' => $typeInput
            ];
            $this->inputModelFields();
        }
    }

    private function choiceDataTypes($dataType, $field): string
    {
        $data_type = Str::camel($this->cleanString($dataType, ' '));
        $type = collect(config('mvc.column_types'))->filter(function ($item) use ($data_type) {
            return Str::lower($item) == Str::lower($data_type);
        })->first();
        if (!is_null($type)) return $type;
        $this->error('Your input "' . $dataType . '" for field "' . $field . '" is not found in the list');
        return $this->choiceDataTypes($this->dataTypes($field), $field);
    }

    private function dataTypes($field): string
    {
        $type = $this->ask('Type data type for field ' . $field . ' / type "list" to see list of data type');
        if (Str::upper($type) == 'LIST') {
            $columnType = config('mvc.column_types');
            $chunks = array_chunk($columnType, 10);
            foreach ($chunks as $chunk) {
                $type = $this->choice('Choose Data Type for ' . $field, collect($chunk)->push('Other')->toArray());
                if ($type != 'Other') break;
            }
        }
        return $type;
    }

    private function cleanString($field, $replaced): string
    {
        return Str::replace(['<', '>', '/', ' ', '-', '_', '(', ')', '[', ']', '{', '}', ':', ';', '"', "'", ',', '.', '?', '!', '@', '#', '$', '%', '^', '&', '*', '+', '=', '|', '\\', '`', '~'], $replaced, $field);
    }
}
