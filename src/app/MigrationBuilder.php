<?php

namespace Arwp\Mvc\app;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Arwp\Mvc\utilities\StringUtil;

trait MigrationBuilder
{
    public function migrationBuilder(): void
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
            $this->fields = collect($this->fields)->prepend(['field'=>'id', 'type'=>'uuid', 'relation'=>false, 'view'=>"No Input Elements"])->toArray();
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
        return StringUtil::replaceEOLWithIndentation($fields,3);
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
        return StringUtil::replaceEOLWithIndentation($fields,3);
    }
}
