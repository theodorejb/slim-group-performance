<?php

/*
 * Test the performance of Slim Framework (https://github.com/slimphp/Slim) with many groups and subgroups.
 */

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

require 'vendor/autoload.php';

function initRoutes($base, $generateUnmatchableRoutes = true)
{
    $startTime = microtime(true);
    $app = new App();
    $groupCount = 50;
    $subgroupCount = 25;
    $initTime = 0;
    $totalRoutes = 0;

    for ($g = 1; $g <= $groupCount; $g++) {
        $app->group("/$base/group{$g}", function () use ($app, $g, $generateUnmatchableRoutes, $groupCount, $subgroupCount, $startTime, &$initTime, &$totalRoutes) {
            if (!$generateUnmatchableRoutes) {
                if ($g === 1) {
                    $subgroupCount = 1; // generate routes only for the matching subgroup
                } else {
                    return;
                }
            }

            for ($s = 1; $s <= $subgroupCount; $s++) {
                $app->group("/sub{$s}", endpointFactory($app, $g, $s, $groupCount, $startTime, $initTime, $totalRoutes));
            }
        });
    }

    $initTime = microtime(true) - $startTime;
    $app->run();
}

function endpointFactory(App $app, $g, $s, $groupCount, $startTime, &$initTime, &$totalRoutes)
{
    $totalRoutes += 6;
    // A real world app would often use an ORM to generate get/post/patch/update/delete methods for each endpoint.
    // The ORM would be passed a unique class name for each subgroup, which it would use inside route callbacks.
    $class = "class" . md5($g * $s);

    return function () use ($app, $class, $groupCount, $startTime, &$initTime, &$totalRoutes) {
        $app->get('', routeFactory($class, 'search', $groupCount, $startTime, $initTime, $totalRoutes));
        $app->get('/{id}', routeFactory($class, 'get by ID', $groupCount, $startTime, $initTime, $totalRoutes));
        $app->post('', routeFactory($class, 'create', $groupCount, $startTime, $initTime, $totalRoutes));
        $app->put('/{id}', routeFactory($class, 'update by ID', $groupCount, $startTime, $initTime, $totalRoutes));
        $app->patch('/{id}', routeFactory($class, 'patch by ID', $groupCount, $startTime, $initTime, $totalRoutes));
        $app->delete('/{id}', routeFactory($class, 'delete by ID', $groupCount, $startTime, $initTime, $totalRoutes));
    };
}

function routeFactory($class, $route, $groupCount, $startTime, &$initTime, &$totalRoutes)
{
    return function (Request $request, Response $response, array $args) use ($class, $route, $groupCount, $startTime, &$initTime, &$totalRoutes) {
        return $response->withJson([
            'class' => $class,
            'route' => $route,
            'args' => $args,
            'totalGroups' => $groupCount,
            'generatedRoutes' => $totalRoutes,
            'initTime' => $initTime,
            'responseTime' => microtime(true) - $startTime,
        ]);
    };
}
