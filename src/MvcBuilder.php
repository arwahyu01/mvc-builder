<?php

namespace Arwp\Mvc;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MvcBuilder
{
    private string $model;
    private array $fields=[];
    private string $path_stub = 'vendor/mvc-builder/arwp/resources/stub';

    public function createMvc($model,$fields): void
    {
        $this->model=$model;
        $this->fields=$fields;
        $this->migrationBuilder();
    }

    /**
     * Function to build migration fields
     * @return void
     */
    private function migrationBuilder(): void
    {
        $path_migrations = database_path('migrations');
        $file_migrations = File::files($path_migrations);
        collect($file_migrations)->map(function ($data) {
            if (Str::contains($data, 'create_' . Str::snake(Str::plural($this->model)) . '_table.php')) {
                File::delete($data);
                info('Migration deleted Successfully');
            }
        });
        info('Creating Migration for ' . $this->model . '...');
        $target = $path_migrations . '/' . date('Y_m_d_His') . '_create_' . Str::snake(Str::plural($this->model)) . '_table.php';
        File::copy(base_path($this->path_stub.'/migration/migration.stub'), $target);
        if (File::exists($target)) {
            $file = File::get($target);
            $replaced = Str::replace('{{ fields }}', $this->createMigrationFields(), $file);
            $replaced = Str::replace('//{{ indexes }}', $this->createParentField(), $replaced);
            $replaced = Str::replace('{{ table }}', Str::snake(Str::plural($this->model)), $replaced);
            File::put($target, $replaced);
            info('Migration created successfully');
            $this->modelBuilder();
        } else {
            info('Migration failed to create, please try again');
        }
    }

    private function createMigrationFields(): array|string
    {
        if (!collect($this->fields)->contains('id')) {
            array_unshift($this->fields, ['field' => 'id', 'type' => 'uuid', 'relation' => FALSE, 'view' => "No Input Elements"]);
        }
        $fields = '';
        foreach ($this->fields as $field) {
            if ($field['type'] == 'uuidMorphs') {
                $fields .= '$table->uuidMorphs("' . $field['field'] . '");' . PHP_EOL;
            } else {
                if (!Str::contains($field['field'], '_morph')) {
                    if ($field['relation']) {
                        if (Str::lower($field['field']) == 'parent_id') {
                            $fields .= '$table->' . $field['type'] . '("' . $field['field'] . '")->nullable();' . PHP_EOL;
                        } else {
                            $fields .= '$table->' . $field['type'] . '("' . $field['field'] . '")->nullable()->constrained();' . PHP_EOL;
                        }
                    } else {
                        if (Str::lower($field['field']) == 'id') {
                            if ($field['type'] == 'uuid') {
                                $fields .= '$table->uuid("id")->primary();' . PHP_EOL;
                            } else {
                                $fields .= '$table->id();' . PHP_EOL;
                            }
                        } else {
                            $fields .= '$table->' . $field['type'] . '("' . $field['field'] . '")->nullable();' . PHP_EOL;
                        }
                    }
                }
            }
        }
        $fields .= '$table->timestamps();' . PHP_EOL;
        $fields .= '$table->softDeletes();';
        return str_replace(PHP_EOL, PHP_EOL . "\t\t\t", $fields);
    }

    private function createParentField(): array|string
    {
        $fields = '';
        foreach ($this->fields as $field) {
            if ($field['relation']) {
                if ($field['field'] == 'parent_id') {
                    $fields .= '$table->foreign("' . $field['field'] . '")->references("id")->on("{{ table }}")->onDelete("cascade");';
                }
            }
        }
        $fields = $fields ? str_replace(PHP_EOL, PHP_EOL . "\t\t\t", $fields) : '//you can add foreign key here';
        return str_replace(PHP_EOL, PHP_EOL . "\t\t\t", $fields);
    }

    /**
     * Function to build model
     * @return void
     */
    private function modelBuilder(): void
    {
        info('Creating model ...');
        $path_model = App::basePath(config('mvc.path_model'));
        $target_model = $path_model . '/' . $this->model . '.php';
        File::copy(base_path($this->path_stub . '/model/model.stub'), $target_model);
        if (File::exists($target_model)) {
            $file = File::get($target_model);
            $replaced = Str::replace('{{ namespace }}', Str::ucfirst(config('mvc.path_model')), $file);
            $replaced = Str::replace('{{ class }}', $this->model, $replaced);
            $replaced = Str::replace('{{ fillable }}', $this->createModelFields(), $replaced);
            $replaced = Str::replaceLast('}', $this->createFunction() . '}', $replaced);
            File::put($target_model, $replaced);
            info('Model created successfully');
            $this->bladeViewBuilder();
        }else{
            info('Model failed to create, please try again');
        }
    }

    private function createFunction() : string
    {
        $function='';
        foreach ($this->fields as $field) {
            if ($field['type'] == 'uuidMorphs') {
                $function.="\tpublic function fileable()".PHP_EOL."\t"."{".PHP_EOL."\t\t"."return \$this->morphTo();".PHP_EOL."\t"."}".PHP_EOL;
            }
            else {
                if(Str::contains($field['field'], '_morph')){
                    $name=Str::replace('_morph', '', $field['field']);
                    $function.="\tpublic function ".$name."()".PHP_EOL."\t"."{".PHP_EOL."\t\t"."return \$this->morphOne(".ucfirst($name)."::class, '".$name."able');".PHP_EOL."\t"."}".PHP_EOL;
                }else{
                    if (Str::contains($field['field'], '_id')) {
                        $name=Str::replace('_id', '', $field['field']);
                        $function.="\tpublic function ".Str::lower($name)."()".PHP_EOL."\t"."{".PHP_EOL."\t\t"."return \$this->belongsTo(".Str::ucfirst(Str::camel($name))."::class);".PHP_EOL."\t"."}".PHP_EOL;
                    }
                }
            }
        }
        return $function;
    }

    private function createModelFields(): string
    {
        $fields='';
        foreach ($this->fields as $field) {
            if ($field['type'] != 'uuidMorphs') {
                if(!Str::contains($field['field'], '_morph')) {
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
        $path=resource_path(config('mvc.path_view').'/'.Str::snake($this->model, '-'));
        $path_index=$path.'/index.blade.php';
        $path_create=$path.'/create.blade.php';
        $path_update=$path.'/edit.blade.php';
        $path_delete=$path.'/delete.blade.php';
        $path_show=$path.'/show.blade.php';
        $path_datatable=$path.'/datatable.blade.php';
        File::makeDirectory($path, 0755, TRUE, TRUE);
        File::copy(base_path($this->path_stub.'/view/index.stub'), $path_index);
        File::copy(base_path($this->path_stub.'/view/create.stub'), $path_create);
        File::copy(base_path($this->path_stub.'/view/edit.stub'), $path_update);
        File::copy(base_path($this->path_stub.'/view/delete.stub'), $path_delete);
        File::copy(base_path($this->path_stub.'/view/show.stub'), $path_show);
        File::copy(base_path($this->path_stub.'/view/datatable.stub'), $path_datatable);
        if (File::exists($path_create) && File::exists($path_index) && File::exists($path_update) && File::exists($path_datatable)) {
            // view index
            $file_index=File::get($path_index);
            $replace_index=Str::replace('{{ $datatable }}', $this->createDataTable(), $file_index);
            $replace_index=Str::replace('{{ page }}', Str::title($this->model), $replace_index);
            File::put($path_index, $replace_index);
            // view create
            $file_create=File::get($path_create);
            $replace_create=Str::replace('{{ $template }}', $this->createFormInput('create'), $file_create);
            $replace_create=Str::replace('{{ page }}', Str::title($this->model), $replace_create);
            File::put($path_create, $replace_create);
            // view edit
            $file_update=File::get($path_update);
            $replace_update=Str::replace('{{ $template }}', $this->createFormInput('edit'), $file_update);
            $replace_update=Str::replace('{{ page }}', Str::title($this->model), $replace_update);
            File::put($path_update, $replace_update);
            // view show
            $file_show=File::get($path_show);
            $replace_show=Str::replace('{{ $template }}', $this->createViewShow(), $file_show);
            File::put($path_show, $replace_show);
            // datatable view
            $file_datatable=File::get($path_datatable);
            $replace_datatable=Str::replace('{{ $columns }}', $this->createDatatableColumns(), $file_datatable);
            File::put($path_datatable, $replace_datatable);
            info('View created successfully');
            $this->controllerBuilder();
        }
    }

    private function createDataTable(): string
    {
        $table = '<table id="datatable" class="table table-bordered table-striped" style="width: 100%;">' . PHP_EOL;
        $table .= "\t" . '<thead>' . PHP_EOL;
        $table .= "\t" . '<tr>' . PHP_EOL;
        $table .= "\t\t" . '<th class="w-0">No</th>' . PHP_EOL;
        foreach ($this->fields as $field) {
            if ($field['type'] != 'uuidMorphs') {
                if ($field['view'] != "No Input Elements") {
                    if ($field['field'] != 'id') {
                        $field_name = Str::replace('_morph', '', $field['field']);
                        $table .= "\t\t" . '<th>' . Str::title(Str::replace('_', ' ', Str::snake(Str::before($field_name, '_id')))) . '</th>' . PHP_EOL;
                    }
                }
            }
        }
        $table .= "\t\t" . '<th class="text-center w-0">Action</th>' . PHP_EOL;
        $table .= "\t" . '</tr>' . PHP_EOL;
        $table .= "\t" . '</thead>' . PHP_EOL;
        $table .= "\t" . '<tbody>' . PHP_EOL;
        $table .= "\t" . '</tbody>' . PHP_EOL;
        $table .= '</table>' . PHP_EOL;
        $table = Str::replace(PHP_EOL, PHP_EOL . "\t\t\t\t\t\t\t\t", $table);
        return Str::beforeLast($table, PHP_EOL);
    }

    private function createDatatableColumns(): string
    {
        $field_columns = '';
        foreach ($this->fields as $field) {
            if ($field['type'] != 'uuidMorphs') {
                if ($field['view'] != "No Input Elements") {
                    if ($field['field'] != 'id') {
                        $field_name = Str::replace('_morph', '', $field['field']);
                        $field_columns .= Str::replace('{{ field }}', $field_name, config('mvc.table')) . PHP_EOL;
                    }
                }
            }
        }
        $field_columns = Str::replace(PHP_EOL, PHP_EOL . "\t\t\t", $field_columns);
        return Str::beforeLast($field_columns, PHP_EOL);
    }

    private function createFormInput(string $action): string
    {
        $field_input='';
        foreach ($this->fields as $field) {
            if ($field['view'] != "No Input Elements") {
                if ($field['type'] != 'uuidMorphs') {
                    $field_name = Str::replace('_morph', '', $field['field']);
                    if ($field['field'] != 'id') {
                        if (collect(collect(config('mvc.view'))->keys()->toArray())->contains($field['view'])) {
                            $field_input .= Str::replace('{{ field }}', $field_name, config('mvc.view.' . $field['view'])) . PHP_EOL . "\t\t";
                        } else {
                            $field_input .= Str::replace('{{ field }}', $field_name, config('mvc.view.text')) . PHP_EOL . "\t\t";
                        }
                        if ($action == 'edit') {
                            $field_input = Str::replace('NULL', '$data->' . $field_name, $field_input);
                        }
                    }
                    $field_input = Str::replace('{{ title }}', Str::title(Str::replace('_', ' ', Str::snake(Str::before($field_name,'_id')))), $field_input);
                }
            }
        }
        return Str::beforeLast($field_input, PHP_EOL);
    }

    private function createViewShow(): string
    {
        $show = '<div class="row">' . PHP_EOL;
        foreach ($this->fields as $field) {
            if ($field['type'] != 'uuidMorphs') {
                if ($field['view'] != "No Input Elements") {
                    $field_name = Str::replace('_morph', '', $field['field']);
                    if ($field['field'] != 'id') {
                        $show .= "\t\t\t" . '<div class="col-md-' . (collect($this->fields)->count() > 1 ? '6' : '12') . '">' . PHP_EOL;
                        $show .= "\t\t\t\t" . '<div class="form-group">' . PHP_EOL;
                        $show .= "\t\t\t\t\t" . '<label>' . Str::title(Str::replace('_', ' ', Str::snake(Str::before($field_name, '_id')))) . '</label>' . PHP_EOL;
                        $show .= "\t\t\t\t\t" . '<input type="text" class="form-control" value="{{ $data->' . $field_name . ' }}" readonly>' . PHP_EOL;
                        $show .= "\t\t\t\t" . '</div>' . PHP_EOL;
                        $show .= "\t\t\t" . '</div>' . PHP_EOL;
                    }
                }
            }
        }
        $show .= "\t\t" . '</div>' . PHP_EOL;
        return Str::beforeLast($show, PHP_EOL);
    }

    /**
     * Function to create controller
     * @return void
     */
    private function controllerBuilder() : void
    {
        $path=App::basePath(config('mvc.path_controller')).'/'.$this->model;
        $path_controller=$path.'/'.$this->model.'Controller.php';
        File::makeDirectory($path, 0755, TRUE, TRUE);
        File::copy(base_path($this->path_stub.'/controller/controller.stub'), $path_controller);
        if (File::exists($path_controller)) {
            $file_controller=File::get($path_controller);
            $replaced=Str::replace('{{ namespace }}', Str::replace('/', '\\', config('mvc.path_controller')).'\\'.$this->model, $file_controller);
            $replaced=Str::replace('{{ class }}', $this->model.'Controller', $replaced);
            $replaced=Str::replace('{{ $validation }}', $this->createValidation(), $replaced);
            File::put($path_controller, $replaced);
            $this->routeBuilder();
            info('Controller created successfully');
        }else{
            info('Controller failed to create');
        }
    }

    private function createValidation(): string
    {
        $fields = '';
        foreach ($this->fields as $field) {
            if ($field['type'] != 'uuidMorphs') {
                if ($field['view'] != "No Input Elements") {
                    if ($field['field'] != 'id') {
                        $fields .= "'" . Str::replace('_morph', '', $field['field']) . "' => 'required'," . PHP_EOL . "\t\t\t";
                    }
                }
            }
        }
        return Str::beforeLast($fields, PHP_EOL);
    }

    /**
     * Function to create new route
     * @return void
     */
    private function routeBuilder() : void
    {
        $lower_name = Str::lower(Str::snake($this->model,'-'));
        $route_resource="\t\t"."Route::resource('".$lower_name."', '".$this->model."\\".$this->model."Controller');";
        $route_data="\t\t"."Route::get('data', '".$this->model."\\".$this->model."Controller@data');".PHP_EOL;
        $route_data.="\t\t\t"."Route::get('delete/{id}', '".$this->model."\\".$this->model."Controller@delete');".PHP_EOL;
        $route_prefix="\t\t"."Route::prefix('".$lower_name."')->as('".$lower_name."')->group(function () {".PHP_EOL."\t".$route_data."\t\t"."});".PHP_EOL;
        $path_route=App::basePath(config('mvc.path_route'));
        $file_route=File::get($path_route);
        if (Str::contains($file_route, $route_resource)) {
            info('Route already exists');
        }
        else {
            $key_replacer = "//{{route replacer}} DON'T REMOVE THIS LINE"; // we use this to replace route
            $replaced=Str::replaceLast($key_replacer, "//".$lower_name.PHP_EOL.$route_prefix.$key_replacer, $file_route);
            $replaced=Str::replaceLast($key_replacer, $route_resource.PHP_EOL."\t//end-".$lower_name.PHP_EOL."\t".$key_replacer, $replaced);
            File::put($path_route, $replaced);
            info('Route created successfully');
        }
    }
}
