<?php

namespace Arwp\Mvc\app;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

trait RouteBuilder
{
    /**
     * Build and update routes for the specified model.
     */
    public function routeBuilder(): void
    {
        // Generate lowercase snake-case version of the model name
        $lower_name = Str::lower(Str::snake($this->model_name, '-'));

        // Generate the resource route definition
        $route_resource = $this->generateResourceRoute($lower_name);

        // Generate additional data routes
        $route_data = $this->generateDataRoutes();

        // Generate a grouped prefix for specific routes
        $route_prefix = $this->generatePrefixRoute($lower_name, $route_data);

        // Get the path to the route file
        $path_route = App::basePath(config('mvc.path_route'));
        $file_route = File::get($path_route);

        // Check if the resource route already exists
        if ($this->routeExists($file_route, $route_resource)) {
            $this->info('Route already exists');
        } else {
            // Placeholder used for updating the route file
            $key_replacer = "//{{route replacer}} DON'T REMOVE THIS LINE";

            // Update the route file with the new routes
            $updated_route = $this->updateRouteFile($file_route, $key_replacer, $lower_name, $route_prefix, $route_resource);

            // Write the updated content back to the route file
            File::put($path_route, $updated_route);

            $this->info('Route created successfully');
        }
    }

    /**
     * Generate the resource route definition.
     *
     * @param string $lower_name
     * @return string
     */
    private function generateResourceRoute(string $lower_name): string
    {
        return "\t\t" . "Route::resource('" . $lower_name . "', '" . $this->model_name . "\\" . $this->model_name . "Controller');";
    }

    /**
     * Generate additional data-related routes.
     *
     * @return string
     */
    private function generateDataRoutes(): string
    {
        $route_data = "\t\t" . "Route::get('data', '" . $this->model_name . "\\" . $this->model_name . "Controller@data');" . PHP_EOL;
        $route_data .= "\t\t\t" . "Route::get('delete/{id}', '" . $this->model_name . "\\" . $this->model_name . "Controller@delete');" . PHP_EOL;
        return $route_data;
    }

    /**
     * Generate a grouped prefix for specific routes.
     *
     * @param string $lower_name
     * @param string $route_data
     * @return string
     */
    private function generatePrefixRoute(string $lower_name, string $route_data): string
    {
        return "\t\t" . "Route::prefix('" . $lower_name . "')->as('" . $lower_name . "')->group(function () {" . PHP_EOL . "\t" . $route_data . "\t\t" . "});" . PHP_EOL;
    }

    /**
     * Check if the resource route already exists in the file.
     *
     * @param string $file_route
     * @param string $route_resource
     * @return bool
     */
    private function routeExists(string $file_route, string $route_resource): bool
    {
        return Str::contains($file_route, $route_resource);
    }

    /**
     * Update the route file with the new route definitions.
     *
     * @param string $file_route
     * @param string $key_replacer
     * @param string $lower_name
     * @param string $route_prefix
     * @param string $route_resource
     * @return string
     */
    private function updateRouteFile(string $file_route, string $key_replacer, string $lower_name, string $route_prefix, string $route_resource): string
    {
        // Replace the placeholder with the grouped prefix and resource route
        $replaced = Str::replaceLast($key_replacer, "//" . $lower_name . PHP_EOL . $route_prefix . $key_replacer, $file_route);

        // Add the resource route after the grouped prefix
        $replaced = Str::replaceLast($key_replacer, $route_resource . PHP_EOL . "\t//end-" . $lower_name . PHP_EOL . "\t" . $key_replacer, $replaced);

        return $replaced;
    }
}
