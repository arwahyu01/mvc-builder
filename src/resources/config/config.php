<?php
/**
 * Created by @arwahyupradana
 * path: config\mvc.php
 * description: this file is used for creating template for generating code in php artisan make:mvc
 * Please don't change this file if you don't understand what you do !!!
 * this file is used for creating template for generating code in php artisan make:mvc
 */

$inputElements = [
    'text' => "<div class='form-group'>\n\t\t\t{!! html()->label()->class('control-label')->for('{{ field }}')->text('{{ title }}') !!}\n\t\t\t{!! html()->text('{{ field }}',NULL)->placeholder('Type {{ title }} here')->class('form-control')->id('{{ field }}') !!}\n\t\t</div>",
    'number' => "<div class='form-group'>\n\t\t\t{!! html()->label()->class('control-label')->for('{{ field }}')->text('{{ title }}') !!}\n\t\t\t{!! html()->number('{{ field }}',NULL)->placeholder('Type {{ title }} here')->class('form-control')->id('{{ field }}') !!}\n\t\t</div>",
    'file' => "<div class='form-group'>\n\t\t\t{!! html()->label()->class('control-label')->for('{{ field }}')->text('{{ title }}') !!}\n\t\t\t{!! html()->file('{{ field }}',NULL)->class('form-control')->id('{{ field }}') !!}\n\t\t</div>",
    'textarea' => "<div class='form-group'>\n\t\t\t{!! html()->label()->class('control-label')->for('{{ field }}')->text('{{ title }}') !!}\n\t\t\t{!! html()->textarea('{{ field }}',NULL)->class('form-control')->id('{{ field }}') !!}\n\t\t</div>",
    'select' => "<div class='form-group'>\n\t\t\t{!! html()->label()->class('control-label')->for('{{ field }}')->text('{{ title }}') !!}\n\t\t\t{!! html()->select('{{ field }}',[])->placeholder('Choose {{ title }} here')->class('form-control')->id('{{ field }}') !!}\n\t\t</div>",
    'select2' => "<div class='form-group'>\n\t\t\t{!! html()->label()->class('control-label')->for('{{ field }}')->text('{{ title }}') !!}\n\t\t\t{!! html()->select('{{ field }}',[])->placeholder('Choose {{ title }} here')->class('form-control select2')->id('{{ field }}') !!}\n\t\t</div>",
    'checkbox' => "<div class='form-group'>\n\t\t\t{!! html()->label()->class('control-label')->for('{{ field }}')->text('{{ title }}') !!}\n\t\t\t{!! html()->checkbox('{{ field }}',false,1)->class('form-control')->id('{{ field }}') !!}\n\t\t</div>",
    'date' => " <div class='form-group'>\n\t\t\t{!! html()->label()->class('control-label')->for('{{ field }}')->text('{{ title }}') !!}\n\t\t\t{!! html()->date('{{ field }}',NULL)->class('form-control')->id('{{ field }}') !!}\n\t\t</div>",
    'dateTime' => " <div class='form-group'>\n\t\t\t{!! html()->label()->class('control-label')->for('{{ field }}')->text('{{ title }}') !!}\n\t\t\t{!! html()->dateTime('{{ field }}',NULL)->class('form-control')->id('{{ field }}') !!}\n\t\t</div>",
    'time' => " <div class='form-group'>\n\t\t\t{!! html()->label()->class('control-label')->for('{{ field }}')->text('{{ title }}') !!}\n\t\t\t{!! html()->time('{{ field }}',NULL)->class('form-control')->id('{{ field }}') !!}\n\t\t</div>",
    'timestamp' => " <div class='form-group'>\n\t\t\t{!! html()->label()->class('control-label')->for('{{ field }}')->text('{{ title }}') !!}\n\t\t\t{!! html()->timestamp('{{ field }}',NULL)->class('form-control')->id('{{ field }}') !!}\n\t\t</div>",
    'radio' => "<div class='form-group'>\n\t\t\t{!! html()->label()->class('control-label')->for('{{ field }}')->text('{{ title }}') !!}<br>\n\t\t\t{!! html()->radio('{{ field }}',false,1)->class('form-control')->id('{{ field }}') !!}\n\t\t</div>"
];
return [
    'path_controller' => 'App/Http/Controllers/Backend', // path to controller folder (default: app/Http/Controllers)
    'path_model' => 'App\Models', // path to model folder (default: app/Models)
    'path_view' => 'views/backend', // path to view folder (default: resources/views)
    'path_route' => 'routes/mvc-route.php', // path to route file (default: routes/mvc-route.php)
    'route_prefix' => 'admin', // Customize with your "Prefix Route" (e.g: 'admin', 'backend' etc.) (optional)
    'view' => $inputElements,
    'table' => "{ data: '{{ field }}' , 'defaultContent':''},",
    'column_types' => [
        "id", "string", "text", "foreignId", "foreignUuid", "bigIncrements", "bigInteger", "json", "longText", "enum", "float", "binary", "boolean", "char", "dateTimeTz", "dateTime", "date",
        "decimal", "double", "foreignIdFor", "foreignUlid", "geometryCollection", "geometry", "increments", "integer", "ipAddress", "jsonb", "lineString", "macAddress", "mediumIncrements",
        "mediumInteger", "mediumText", "morphs", "multiLineString", "multiPoint", "multiPolygon", "nullableMorphs", "nullableTimestamps", "nullableUlidMorphs", "nullableUuidMorphs", "point",
        "polygon", "rememberToken", "set", "smallIncrements", "smallInteger", "softDeletesTz", "softDeletes", "timeTz", "time", "timestampTz", "timestamp", "timestampsTz", "timestamps",
        "tinyIncrements", "tinyInteger", "tinyText", "unsignedBigInteger", "unsignedDecimal", "unsignedInteger", "unsignedMediumInteger", "unsignedSmallInteger", "unsignedTinyInteger", "ulidMorphs",
        "uuidMorphs", "ulid", "uuid", "year",
    ]
];
