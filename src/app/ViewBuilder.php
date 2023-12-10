<?php

namespace Arwp\Mvc\app;

use Arwp\Mvc\utilities\StringUtil;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

trait ViewBuilder
{
    use StringUtil;

    /**
     * Build and create views for the specified model.
     */
    private function ViewBuilder(): void
    {
        // Build the path to the view directory
        $path = resource_path(config('mvc.path_view') . '/' . Str::snake($this->model_name, '-'));

        // Create the directory for the views
        $this->createDirectory($path);

         // Copy stubs to the specified paths with replacements
        $this->copyStubToPath('/view/index.stub', $path . '/index.blade.php', ['{{ $datatable }}' => $this->createDataTable(), '{{ page }}' => Str::title($this->model_name)]);
        $this->copyStubToPath('/view/create.stub', $path . '/create.blade.php', ['{{ $template }}' => $this->createFormInput('create'), '{{ page }}' => Str::title($this->model_name)]);
        $this->copyStubToPath('/view/edit.stub', $path . '/edit.blade.php', ['{{ $template }}' => $this->createFormInput('edit'), '{{ page }}' => Str::title($this->model_name)]);
        $this->copyStubToPath('/view/delete.stub', $path . '/delete.blade.php');
        $this->copyStubToPath('/view/show.stub', $path . '/show.blade.php', ['{{ $template }}' => $this->createViewShow()]);
        $this->copyStubToPath('/view/datatable.stub', $path . '/datatable.blade.php', ['{{ $columns }}' => $this->createDatatableColumns()]);

        $this->info('View created successfully');
    }

    /**
     * Copy a stub file to a specified path with replacements.
     *
     * @param string $stub
     * @param string $path
     * @param array $replacements
     */
    private function copyStubToPath(string $stub, string $path, array $replacements = []): void
    {
         // Read the content of the stub file
        $file_content = File::get($this->path_stub . $stub);

        // Replace placeholders in the stub file with actual values
        foreach ($replacements as $search => $replace) {
            $file_content = Str::replace($search, $replace, $file_content);
        }

        // Write the updated content to the specified path
        File::put($path, $file_content);
    }

    /**
     * Check if a field should be included in the view.
     *
     * @param array $field
     * @return bool
     */
    private function shouldIncludeField(array $field): bool
    {
        return $field['type'] != 'uuidMorphs' && $field['view'] != "No Input Elements" && $field['field'] != 'id';
    }

    /**
     * Create the HTML for a DataTable in the view.
     *
     * @return string
     */
    private function createDataTable(): string
    {
        $table = '<table id="datatable" class="table table-bordered table-striped" style="width: 100%;">' . PHP_EOL;
        $table .= "\t" . '<thead>' . PHP_EOL;
        $table .= "\t" . '<tr>' . PHP_EOL;
        $table .= "\t\t" . '<th class="w-0">No</th>' . PHP_EOL;

        foreach ($this->fields as $field) {
            if ($this->shouldIncludeField($field)) {
                $fieldName = Str::replace('_morph', '', $field['field']);
                $table .= "\t\t" . '<th>' . Str::title(Str::replace('_', ' ', Str::snake(Str::before($fieldName, '_id')))) . '</th>' . PHP_EOL;
            }
        }

        $table .= "\t\t" . '<th class="text-center w-0">Action</th>' . PHP_EOL;
        $table .= "\t" . '</tr>' . PHP_EOL;
        $table .= "\t" . '</thead>' . PHP_EOL;
        $table .= "\t" . '<tbody>' . PHP_EOL;
        $table .= "\t" . '</tbody>' . PHP_EOL;
        $table .= '</table>' . PHP_EOL;

        return Str::beforeLast(StringUtil::replaceEOLWithIndentation($table, 8), PHP_EOL);
    }

    /**
     * Create HTML for DataTable columns.
     *
     * @return string
     */
    private function createDatatableColumns(): string
    {
        $fieldColumns = '';

        foreach ($this->fields as $field) {
            if ($this->shouldIncludeField($field)) {
                $fieldName = Str::replace('_morph', '', $field['field']);
                $fieldColumns .= Str::replace('{{ field }}', $fieldName, config('mvc.table')) . PHP_EOL;
            }
        }

        return Str::beforeLast(StringUtil::replaceEOLWithIndentation($fieldColumns, 3), PHP_EOL);
    }

    /**
     * Create HTML for form input elements.
     *
     * @param string $action
     * @return string
     */
    private function createFormInput(string $action): string
    {
        $fieldInput = '';

        foreach ($this->fields as $field) {
            if ($field['view'] != "No Input Elements" && $field['type'] != 'uuidMorphs') {
                $fieldName = Str::replace('_morph', '', $field['field']);

                if ($field['field'] != 'id') {
                    if ($field['view'] == 'radio') {
                        $radio = $this->createRadioInput($field);
                        $label = $this->createLabel($fieldName);
                        $form = "<div class='form-group row'>" . PHP_EOL . "\t\t\t" . $label . PHP_EOL . "\t\t\t" . $radio . '</div>' . PHP_EOL;
                        $fieldInput .= $form;
                    } else {
                        $fieldInput .= $this->createViewInput($field, $fieldName, $action);
                    }
                }

                $fieldInput = Str::replace('{{ title }}', Str::title(Str::replace('_', ' ', Str::snake(Str::before($fieldName, '_id')))), $fieldInput);
            }
        }

        return Str::beforeLast($fieldInput, PHP_EOL);
    }

    /**
     * Create HTML for radio input elements.
     *
     * @param array $field
     * @return string
     */
    private function createRadioInput(array $field): string
    {
        $radio = '';

        foreach ($field['options'] as $item) {
            $input = Str::replace('{{ field }}', $field['name'], config('mvc.view.radio'));
            $input = Str::replace('{{ true }}', $item[2], $input);
            $radio .= Str::replace('{{ key }}', $item[0], Str::replace('{{ value }}', $item[1], $input)) . PHP_EOL . "\t\t\t";
        }

        return $radio;
    }

    /**
     * Create HTML for a label element.
     *
     * @param string $fieldName
     * @return string
     */
    private function createLabel(string $fieldName): string
    {
        return '{!! html()->span()->text("' . Str::title(Str::replace('_', ' ', Str::snake(Str::before($fieldName, '_id')))) . '")->class("control-label") !!}';
    }

    /**
     * Create HTML for a view input element.
     *
     * @param array $field
     * @param string $fieldName
     * @param string $action
     * @return string
     */
    private function createViewInput(array $field, string $fieldName, string $action): string
    {
        $inputTemplate = collect(config('mvc.view'))->keys()->contains($field['view']) ? config('mvc.view.' . $field['view']) : config('mvc.view.text');

        $fieldInput = Str::replace('{{ field }}', $fieldName, $inputTemplate) . PHP_EOL . "\t\t";

        if ($action == 'edit') {
            $fieldInput = Str::replace('NULL', '$data->' . $fieldName, $fieldInput);
        }

        return $fieldInput;
    }

    /**
     * Create HTML for a view show element.
     *
     * @return string
     */
    private function createViewShow(): string
    {
        $show = '<div class="row">' . PHP_EOL;
        foreach ($this->fields as $field) {
            if ($this->shouldIncludeField($field)) {
                $field_name = Str::replace('_morph', '', $field['field']);
                $show .= "\t\t\t" . '<div class="col-md-' . (collect($this->fields)->count() > 1 ? '6' : '12') . '">' . PHP_EOL;
                $show .= "\t\t\t\t" . '<div class="form-group">' . PHP_EOL;
                $show .= "\t\t\t\t\t" . '{!! html()->span()->text("' . Str::title(Str::replace('_', ' ', Str::snake(Str::before($field_name, '_id')))) . '")->class("control-label") !!}' . PHP_EOL;
                $show .= "\t\t\t\t\t" . '{!! html()->p($data->' . $field_name . ')->class("form-control") !!}' . PHP_EOL;
                $show .= "\t\t\t\t" . '</div>' . PHP_EOL;
                $show .= "\t\t\t" . '</div>' . PHP_EOL;
            }
        }
        $show .= "\t\t" . '</div>' . PHP_EOL;
        return Str::beforeLast($show, PHP_EOL);
    }
}
