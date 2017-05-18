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
    $subgroupCount = 50;
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
                $app->group("/sub{$s}", endpointFactory($app, $groupCount, $startTime, $initTime, $totalRoutes));
            }
        });
    }

    $initTime = microtime(true) - $startTime;
    $app->run();
}

function endpointFactory(App $app, $groupCount, $startTime, &$initTime, &$totalRoutes)
{
    // a real world app would often use an ORM to generate get/post/patch/update/delete methods for each endpoint
    $routeCount = 6;
    $totalRoutes += $routeCount;

    return function () use ($app, $groupCount, $routeCount, $startTime, &$initTime, &$totalRoutes) {
        for ($r = 1; $r <= $routeCount; $r++) {
            $app->get("/route{$r}", function (Request $request, Response $response) use ($groupCount, $startTime, &$initTime, &$totalRoutes) {
                return $response->withJson([
                    'totalGroups' => $groupCount,
                    'generatedRoutes' => $totalRoutes,
                    'initTime' => $initTime,
                    'responseTime' => microtime(true) - $startTime,
                ]);
            });
        }
    };
}
