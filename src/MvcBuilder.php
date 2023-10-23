<?php

namespace Arwp\Mvc;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MvcBuilder extends GeneratorCommand
{
    protected $signature='make:mvc {model} {mvc? : MVC Name}';
    protected $description='Create a new MVC, Model, View, Controller, Route, Migration, Factory';
    private string $model_name;
    private bool $model;
    private bool $view;
    private bool $controller;
    private bool $route;
    private bool $migration;
    private array $fields=[];
    private string $path_stub=__DIR__.'/resources/stub';

    protected function getStub()
    {
    }

    public function handle()
    {
        $this->view=$this->controller=$this->route=$this->migration=$this->model=true;
        $prefix=['migration', 'model', 'view', 'controller', 'route'];
        $mvc=Str::lower($this->argument('mvc') ?? '');
        if ($mvc) {
            if (in_array($mvc, $prefix)) {
                $this->model=Str::contains($mvc, 'model');
                $this->view=Str::contains($mvc, 'view');
                $this->controller=Str::contains($mvc, 'controller');
                $this->route=Str::contains($mvc, 'route');
                $this->migration=Str::contains($mvc, 'migration');
            } else {
                $this->model=Str::contains($mvc, 'm');      // m for model
                $this->view=Str::contains($mvc, 'v');       // v for view
                $this->controller=Str::contains($mvc, 'c'); // c for controller
                $this->route=Str::contains($mvc, 'r');      // r for route
                $this->migration=Str::contains($mvc, 't');  // t for table (migration)
            }
        }
        $this->model_name=Str::ucfirst(Str::camel($this->cleanString($this->argument('model'), ' ')));
        $check_model=File::exists(App::basePath(config('mvc.path_model')).'/'.$this->model_name.'.php');
        $check_view=File::exists(resource_path(config('mvc.path_view').'/'.Str::snake($this->model_name, '-')));
        $check_controller=File::exists(App::basePath(config('mvc.path_controller')).'/'.$this->model_name.'Controller.php');
        if ($check_controller || $check_model || $check_view) {
            $this->info('Oops, your module '.$this->model_name.' already exists !');
            if ($this->confirm('Do you want to delete it, and create a new one ?')) {
                $this->call('delete:mvc', ['module'=>$this->model_name]);
                $this->build();
            } else {
                $this->info('Finished, nothing changed !');
            }
        } else {
            $this->build();
        }
        return true;
    }

    public function build(): void
    {
        $this->info('Preparing to create MVC for '.$this->model_name);
        $mvc=Str::lower($this->argument('mvc') ?? '');
        if ($mvc == 'route' || $mvc == 'r') {
            $this->routeBuilder();
        } else {
            $this->inputModelFields();
            if ($mvc == 'migration' || $mvc == 't') {
                $this->migrationBuilder();
            } elseif ($mvc == 'model' || $mvc == 'm') {
                $this->modelBuilder();
            } elseif ($mvc == 'view' || $mvc == 'v') {
                $this->bladeViewBuilder();
            } elseif ($mvc == 'controller' || $mvc == 'c') {
                $this->controllerBuilder();
            } else {
                if ($this->migration) {
                    $this->migrationBuilder();
                }
                if ($this->model) {
                    $this->modelBuilder();
                }
                if ($this->view) {
                    $this->bladeViewBuilder();
                }
                if ($this->controller) {
                    $this->controllerBuilder();
                }
                if ($this->route) {
                    $this->routeBuilder();
                }
            }
        }
        $this->info('MVC '.$this->model_name.' created successfully');
        $this->call('route:clear');
        $this->call('view:clear');
        $this->call('cache:clear');
        $this->info('Finished');
        $this->info('Please Run "php artisan migrate" to migrate database');
    }

    private function inputModelFields(): void
    {
        $field=$this->ask('Type a field name (push enter to skip)');
        if ($field != '') {
            $field=Str::lower($this->cleanString($field, '_'));
            $dataType=$this->chooseDataTypes($this->dataTypes($field), $field);
            if ($this->model) {
                $relation=$this->confirm('This field has a relation ? '.$field);
                if ($relation) {
                    $model=Str::ucfirst(Str::camel(Str::replace('_id', '', $field)));
                    if (File::exists(App::basePath(config('mvc.path_model').'/'.$model.'.php'))) {
                        $type_relation=$this->choice('Choose Relation Type', ['belongsTo', 'hasMany', 'hasOne', 'belongsToMany'], 0);
                    } else {
                        $this->warn('Model '.$model.' not found, please create it first !');
                        $this->inputModelFields();
                    }
                }
                $this->info('Field : "'.$field.'", Type : "'.$dataType.'", Relation : "'.($relation ? 'Yes, '.($type_relation ?? '') : 'No').'"');
            }
            if ($this->view) {
                $choice=collect(collect(config('mvc.view'))->keys())->prepend('No Input Elements')->toArray();
                $typeInput=$this->choice('Choose Input Element', $choice, 0);
                if ($typeInput == 'radio') {
                    $options=[];
                    $option=$this->ask('Type option value {key:value:true/false} (push enter to skip)');
                    while ($option != '') {
                        $options[]=explode(':', $option);
                        $option=$this->ask('Type option value {key:value:true/false} (push enter to skip)');
                    }
                }
                $this->info('Input Element : "'.$typeInput.'"');
            }
            $this->fields[]=[
                'field'=>$field, 'type'=>$dataType, 'view'=>$typeInput ?? null, 'relation'=>$relation ?? false, 'type_relation'=>$type_relation ?? null, 'options'=>$options ?? [],
            ];
            $this->inputModelFields(); // recursive
        }
    }

    private function chooseDataTypes($dataType, $field): string
    {
        $data_type=Str::camel($this->cleanString($dataType, ' '));
        $type=collect(config('mvc.column_types'))->filter(function ($item) use ($data_type) {
            return Str::lower($item) == Str::lower($data_type);
        })->first();
        if (!is_null($type)) {
            return $type;
        }
        $this->error('Your input "'.$dataType.'" for field "'.$field.'" is not found in the list');
        return $this->chooseDataTypes($this->dataTypes($field), $field);
    }

    private function dataTypes($field): string
    {
        $type=$this->ask('Type data type for field '.$field.' / type "list" to see list of data type');
        if (Str::upper($type) == 'LIST') {
            $columnType=config('mvc.column_types');
            $chunks=array_chunk($columnType, 10);
            foreach ($chunks as $chunk) {
                $type=$this->choice('Choose Data Type for '.$field, collect($chunk)->push('Other')->toArray());
                if ($type != 'Other') {
                    break;
                }
            }
        }
        return $type;
    }

    /**
     * Function to build migration fields
     * @return void
     */
    private function migrationBuilder(): void
    {
        $path_migrations=database_path('migrations');
        $file_migrations=File::files($path_migrations);
        collect($file_migrations)->map(function ($data) {
            if (Str::contains($data, 'create_'.Str::snake(Str::plural($this->model_name)).'_table.php')) {
                File::delete($data);
                $this->info('Migration deleted Successfully');
            }
        });
        $this->info('Creating Migration for '.$this->model_name.'...');
        $target=$path_migrations.'/'.date('Y_m_d_His').'_create_'.Str::snake(Str::plural($this->model_name)).'_table.php';
        File::copy($this->path_stub.'/migration/migration.stub', $target);
        if (File::exists($target)) {
            $file=File::get($target);
            $replaced=Str::replace('{{ fields }}', $this->createMigrationFields(), $file);
            $replaced=Str::replace('//{{ indexes }}', $this->createParentField(), $replaced);
            $replaced=Str::replace('{{ table }}', Str::snake(Str::plural($this->model_name)), $replaced);
            File::put($target, $replaced);
            $this->info('Migration created successfully');
        } else {
            $this->info('Migration failed to create, please try again');
        }
    }

    private function createMigrationFields(): array|string
    {
        if (!collect($this->fields)->contains('id')) {
            array_unshift($this->fields, ['field'=>'id', 'type'=>'uuid', 'relation'=>false, 'view'=>"No Input Elements"]);
        }
        $fields='';
        foreach ($this->fields as $field) {
            if ($field['type'] == 'uuidMorphs') {
                $fields.='$table->uuidMorphs("'.$field['field'].'");'.PHP_EOL;
            } else {
                if (!Str::contains($field['field'], '_morph')) {
                    if ($field['relation']) {
                        if (Str::lower($field['field']) == 'parent_id') {
                            $fields.='$table->'.$field['type'].'("'.$field['field'].'")->nullable();'.PHP_EOL;
                        } else {
                            $fields.='$table->'.$field['type'].'("'.$field['field'].'")->nullable()->constrained();'.PHP_EOL;
                        }
                    } else {
                        if (Str::lower($field['field']) == 'id') {
                            if ($field['type'] == 'uuid') {
                                $fields.='$table->uuid("id")->primary();'.PHP_EOL;
                            } else {
                                $fields.='$table->id();'.PHP_EOL;
                            }
                        } else {
                            $fields.='$table->'.$field['type'].'("'.$field['field'].'")->nullable();'.PHP_EOL;
                        }
                    }
                }
            }
        }
        $fields.='$table->timestamps();'.PHP_EOL;
        $fields.='$table->softDeletes();';
        return str_replace(PHP_EOL, PHP_EOL."\t\t\t", $fields);
    }

    private function createParentField(): array|string
    {
        $fields='';
        foreach ($this->fields as $field) {
            if ($field['relation']) {
                if ($field['field'] == 'parent_id') {
                    $fields.='$table->foreign("'.$field['field'].'")->references("id")->on("{{ table }}")->onDelete("cascade");';
                }
            }
        }
        $fields=$fields ? str_replace(PHP_EOL, PHP_EOL."\t\t\t", $fields) : '//you can add foreign key here';
        return str_replace(PHP_EOL, PHP_EOL."\t\t\t", $fields);
    }

    /**
     * Function to build model
     * @return void
     */
    private function modelBuilder(): void
    {
        $this->info('Creating model ...');
        $path_model=App::basePath(config('mvc.path_model'));
        $target_model=$path_model.'/'.$this->model_name.'.php';
        File::copy($this->path_stub.'/model/model.stub', $target_model);
        if (File::exists($target_model)) {
            $file=File::get($target_model);
            $replaced=Str::replace('{{ namespace }}', Str::ucfirst(Str::replace('/', '\\', config('mvc.path_model'))), $file);
            $replaced=Str::replace('{{ class }}', $this->model_name, $replaced);
            $replaced=Str::replace('{{ fillable }}', $this->createModelFields(), $replaced);
            $replaced=Str::replaceLast('}', $this->createRelations().'}', $replaced);
            File::put($target_model, $replaced);
            $this->info('Model created successfully');
        } else {
            $this->info('Model failed to create, please try again');
        }
    }

    private function createRelations(): string
    {
        $function='';
        foreach ($this->fields as $field) {
            if (Str::contains($field['field'], '_id') && $field['relation']) {
                $name=Str::replace('_id', '', $field['field']);
                if ($field['field'] == 'parent_id') {
                    $target_relation="(".$this->model_name."::class);";
                } else {
                    $target_relation="(".Str::ucfirst(Str::camel($name))."::class);";
                }
                $function.="\t"."public function ".Str::lower($name)."()".PHP_EOL."\t"."{".PHP_EOL."\t\t"."return \$this->".$field['type_relation'].$target_relation.PHP_EOL."\t"."}".PHP_EOL;
            }
        }
        return PHP_EOL.$function;
    }

    private function createModelFields(): string
    {
        $fields='';
        foreach ($this->fields as $field) {
            if ($field['type'] != 'uuidMorphs') {
                if (!Str::contains($field['field'], '_morph')) {
                    $fields.="'".$field['field']."',";
                }
            }
        }
        return Str::beforeLast($fields, ',');
    }

    /**
     * Function to build view
     * @return void
     */
    private function bladeViewBuilder(): void
    {
        $path=resource_path(config('mvc.path_view').'/'.Str::snake($this->model_name, '-'));
        $path_index=$path.'/index.blade.php';
        $path_create=$path.'/create.blade.php';
        $path_update=$path.'/edit.blade.php';
        $path_delete=$path.'/delete.blade.php';
        $path_show=$path.'/show.blade.php';
        $path_datatable=$path.'/datatable.blade.php';
        File::makeDirectory($path, 0755, true, true);
        File::copy($this->path_stub.'/view/index.stub', $path_index);
        File::copy($this->path_stub.'/view/create.stub', $path_create);
        File::copy($this->path_stub.'/view/edit.stub', $path_update);
        File::copy($this->path_stub.'/view/delete.stub', $path_delete);
        File::copy($this->path_stub.'/view/show.stub', $path_show);
        File::copy($this->path_stub.'/view/datatable.stub', $path_datatable);
        if (File::exists($path_create) && File::exists($path_index) && File::exists($path_update) && File::exists($path_datatable)) {
            // view index
            $file_index=File::get($path_index);
            $replace_index=Str::replace('{{ $datatable }}', $this->createDataTable(), $file_index);
            $replace_index=Str::replace('{{ page }}', Str::title($this->model_name), $replace_index);
            File::put($path_index, $replace_index);
            // view create
            $file_create=File::get($path_create);
            $replace_create=Str::replace('{{ $template }}', $this->createFormInput('create'), $file_create);
            $replace_create=Str::replace('{{ page }}', Str::title($this->model_name), $replace_create);
            File::put($path_create, $replace_create);
            // view edit
            $file_update=File::get($path_update);
            $replace_update=Str::replace('{{ $template }}', $this->createFormInput('edit'), $file_update);
            $replace_update=Str::replace('{{ page }}', Str::title($this->model_name), $replace_update);
            File::put($path_update, $replace_update);
            // view show
            $file_show=File::get($path_show);
            $replace_show=Str::replace('{{ $template }}', $this->createViewShow(), $file_show);
            File::put($path_show, $replace_show);
            // datatable view
            $file_datatable=File::get($path_datatable);
            $replace_datatable=Str::replace('{{ $columns }}', $this->createDatatableColumns(), $file_datatable);
            File::put($path_datatable, $replace_datatable);
            $this->info('View created successfully');
        }
    }

    private function createDataTable(): string
    {
        $table='<table id="datatable" class="table table-bordered table-striped" style="width: 100%;">'.PHP_EOL;
        $table.="\t".'<thead>'.PHP_EOL;
        $table.="\t".'<tr>'.PHP_EOL;
        $table.="\t\t".'<th class="w-0">No</th>'.PHP_EOL;
        foreach ($this->fields as $field) {
            if ($field['type'] != 'uuidMorphs' && $field['view'] != "No Input Elements" && $field['field'] != 'id') {
                $field_name=Str::replace('_morph', '', $field['field']);
                $table.="\t\t".'<th>'.Str::title(Str::replace('_', ' ', Str::snake(Str::before($field_name, '_id')))).'</th>'.PHP_EOL;
            }
        }
        $table.="\t\t".'<th class="text-center w-0">Action</th>'.PHP_EOL;
        $table.="\t".'</tr>'.PHP_EOL;
        $table.="\t".'</thead>'.PHP_EOL;
        $table.="\t".'<tbody>'.PHP_EOL;
        $table.="\t".'</tbody>'.PHP_EOL;
        $table.='</table>'.PHP_EOL;
        $table=Str::replace(PHP_EOL, PHP_EOL."\t\t\t\t\t\t\t\t", $table);
        return Str::beforeLast($table, PHP_EOL);
    }

    private function createDatatableColumns(): string
    {
        $field_columns='';
        foreach ($this->fields as $field) {
            if ($field['type'] != 'uuidMorphs' && $field['view'] != "No Input Elements" && $field['field'] != 'id') {
                $field_name=Str::replace('_morph', '', $field['field']);
                $field_columns.=Str::replace('{{ field }}', $field_name, config('mvc.table')).PHP_EOL;
            }
        }
        $field_columns=Str::replace(PHP_EOL, PHP_EOL."\t\t\t", $field_columns);
        return Str::beforeLast($field_columns, PHP_EOL);
    }

    private function createFormInput(string $action): string
    {
        $field_input='';
        foreach ($this->fields as $field) {
            if ($field['view'] != "No Input Elements" && $field['type'] != 'uuidMorphs') {
                $field_name=Str::replace('_morph', '', $field['field']);
                if ($field['field'] != 'id') {
                    if ($field['view'] == 'radio') {
                        $radio='';
                        foreach ($field['options'] as $item) {
                            $input=Str::replace('{{ field }}', $field_name, config('mvc.view.radio'));
                            $input=Str::replace('{{ true }}', $item[2], $input);
                            $radio.=Str::replace('{{ key }}', $item[0], Str::replace('{{ value }}', $item[1], $input)).PHP_EOL."\t\t\t";
                        }
                        $label='{!! html()->span()->text("'.Str::title(Str::replace('_', ' ', Str::snake(Str::before($field_name, '_id')))).'")->class("control-label") !!}';
                        $form="<div class='form-group row'>".PHP_EOL;
                        $form.="\t\t\t".$label.PHP_EOL;
                        $form.="\t\t\t".$radio;
                        $form.='</div>'.PHP_EOL;
                        $field_input.=$form;
                    } else {
                        if (collect(collect(config('mvc.view'))->keys()->toArray())->contains($field['view'])) {
                            $field_input.=Str::replace('{{ field }}', $field_name, config('mvc.view.'.$field['view'])).PHP_EOL."\t\t";
                        } else {
                            $field_input.=Str::replace('{{ field }}', $field_name, config('mvc.view.text')).PHP_EOL."\t\t";
                        }
                        if ($action == 'edit') {
                            $field_input=Str::replace('NULL', '$data->'.$field_name, $field_input);
                        }
                    }
                }
                $field_input=Str::replace('{{ title }}', Str::title(Str::replace('_', ' ', Str::snake(Str::before($field_name, '_id')))), $field_input);
            }
        }
        return Str::beforeLast($field_input, PHP_EOL);
    }

    private function createViewShow(): string
    {
        $show='<div class="row">'.PHP_EOL;
        foreach ($this->fields as $field) {
            if ($field['type'] != 'uuidMorphs' && $field['view'] != "No Input Elements" && $field['field'] != 'id') {
                $field_name=Str::replace('_morph', '', $field['field']);
                $show.="\t\t\t".'<div class="col-md-'.(collect($this->fields)->count() > 1 ? '6' : '12').'">'.PHP_EOL;
                $show.="\t\t\t\t".'<div class="form-group">'.PHP_EOL;
                $show.="\t\t\t\t\t".'{!! html()->span()->text("'.Str::title(Str::replace('_', ' ', Str::snake(Str::before($field_name, '_id')))).'")->class("control-label") !!}'.PHP_EOL;
                $show.="\t\t\t\t\t".'{!! html()->p($data->'.$field_name.')->class("form-control") !!}'.PHP_EOL;
                $show.="\t\t\t\t".'</div>'.PHP_EOL;
                $show.="\t\t\t".'</div>'.PHP_EOL;
            }
        }
        $show.="\t\t".'</div>'.PHP_EOL;
        return Str::beforeLast($show, PHP_EOL);
    }

    /**
     * Function to create controller
     * @return void
     */
    private function controllerBuilder(): void
    {
        $path=App::basePath(config('mvc.path_controller')).'/'.$this->model_name;
        $path_controller=$path.'/'.$this->model_name.'Controller.php';
        File::makeDirectory($path, 0755, true, true);
        File::copy($this->path_stub.'/controller/controller.stub', $path_controller);
        if (File::exists($path_controller)) {
            $file_controller=File::get($path_controller);
            $replaced=Str::replace('{{ namespace }}', Str::ucfirst(Str::replace('/', '\\', config('mvc.path_controller'))).'\\'.$this->model_name, $file_controller);
            $replaced=Str::replace('{{ class }}', $this->model_name.'Controller', $replaced);
            $replaced=Str::replace('{{ $validation }}', $this->createValidation(), $replaced);
            File::put($path_controller, $replaced);
            $this->info('Controller created successfully');
        } else {
            $this->info('Controller failed to create');
        }
    }

    private function createValidation(): string
    {
        $fields='';
        foreach ($this->fields as $field) {
            if ($field['type'] != 'uuidMorphs' && $field['view'] != "No Input Elements" && $field['field'] != 'id') {
                $fields.="'".Str::replace('_morph', '', $field['field'])."' => 'required',".PHP_EOL."\t\t\t";
            }
        }
        return Str::beforeLast($fields, PHP_EOL);
    }

    /**
     * Function to create new route
     * @return void
     */
    private function routeBuilder(): void
    {
        $lower_name=Str::lower(Str::snake($this->model_name, '-'));
        $route_resource="\t\t"."Route::resource('".$lower_name."', '".$this->model_name."\\".$this->model_name."Controller');";
        $route_data="\t\t"."Route::get('data', '".$this->model_name."\\".$this->model_name."Controller@data');".PHP_EOL;
        $route_data.="\t\t\t"."Route::get('delete/{id}', '".$this->model_name."\\".$this->model_name."Controller@delete');".PHP_EOL;
        $route_prefix="\t\t"."Route::prefix('".$lower_name."')->as('".$lower_name."')->group(function () {".PHP_EOL."\t".$route_data."\t\t"."});".PHP_EOL;
        $path_route=App::basePath(config('mvc.path_route'));
        $file_route=File::get($path_route);
        if (Str::contains($file_route, $route_resource)) {
            $this->info('Route already exists');
        } else {
            $key_replacer="//{{route replacer}} DON'T REMOVE THIS LINE"; // we use this to replace route
            $replaced=Str::replaceLast($key_replacer, "//".$lower_name.PHP_EOL.$route_prefix.$key_replacer, $file_route);
            $replaced=Str::replaceLast($key_replacer, $route_resource.PHP_EOL."\t//end-".$lower_name.PHP_EOL."\t".$key_replacer, $replaced);
            File::put($path_route, $replaced);
            $this->info('Route created successfully');
        }
    }

    private function cleanString($field, $replaced): string
    {
        return Str::replace(['<', '>', '/', ' ', '-', '_', '(', ')', '[', ']', '{', '}', ':', ';', '"', "'", ',', '.', '?', '!', '@', '#', '$', '%', '^', '&', '*', '+', '=', '|', '\\', '`', '~'],
            $replaced, $field);
    }
}
