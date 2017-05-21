<?php

/*
 * Test the performance of Slim Framework (https://github.com/slimphp/Slim) with many groups and subgroups.
 */

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

require 'vendor/autoload.php';

$startTime = microtime(true);
$initTime = 0;
$totalRoutes = 0;

function initRoutes($base, $generateUnmatchableRoutes = true)
{
    global $startTime, $initTime;

    $app = new App();
    $groupCount = 50;
    $subgroupCount = 25;

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
                $app->group("/sub{$s}", endpointFactory($app, $g, $s));
            }
        });
    }

    $initTime = microtime(true) - $startTime;
    $app->run();
}

function endpointFactory(App $app, $g, $s)
{
    global $totalRoutes;

    $totalRoutes += 6;
    // A real world app would often use an ORM to generate get/post/patch/update/delete methods for each endpoint.
    // The ORM would be passed a unique class name for each subgroup, which it would use inside route callbacks.
    $class = "class" . md5($g * $s);

    return function () use ($app, $class) {
        $app->get('', 'handler')->setArgument('entity', $class);
        $app->get('/{id}', 'handler')->setArgument('entity', $class);
        $app->post('', 'handler')->setArgument('entity', $class);
        $app->put('/{id}', 'handler')->setArgument('entity', $class);
        $app->patch('/{id}', 'handler')->setArgument('entity', $class);
        $app->delete('/{id}', 'handler')->setArgument('entity', $class);
    };
}

// in a real-world app there would be separate delete/patch/update/etc. handler functions
function handler(Request $request, Response $response, array $args)
{
    global $totalRoutes, $startTime, $initTime;

    return $response->withJson([
        'class' => $request->getAttribute('entity'),
        'args' => $args,
        'generatedRoutes' => $totalRoutes,
        'initTime' => $initTime,
        'responseTime' => microtime(true) - $startTime,
    ]);
}
