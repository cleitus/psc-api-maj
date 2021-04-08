<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

use Illuminate\Support\Facades\URL;
use Laravel\Lumen\Routing\Router;

$proxy_url    = env('PROXY_URL');
$proxy_schema = env('PROXY_SCHEMA');
$nationalIdRegex = '^[0-9]+\/|[0-9]|\-[0-9]+$Z';

if(!empty($proxy_url)) {
    URL::($proxy_url);
}

if(!empty($proxy_schema)) {
    URL::forceScheme($proxy_schema);
}

/** @var Router $router */

$router->get('/', function () use ($router) {
    return $router->app->version();
});
$router->group(['prefix' => 'api'], function () use ($router, $nationalIdRegex) {
    $router->get('ps',  ['uses' => 'PsController@index']);
    $router->post('ps',  ['uses' => 'PsController@store']);
    $router->put('ps', ['uses' => 'PsController@storeOrUpdate']);
    $router->get("ps/{psId}", ['uses' => 'PsController@show']);
    $router->put("ps/{psId:$nationalIdRegex}", ['uses' => 'PsController@update']);
    $router->delete("ps/{psId:$nationalIdRegex}", ['uses' => 'PsController@destroy']);

    $router->get("ps/{psId:$nationalIdRegex}/professions", ['uses' => 'ProfessionController@index']);
    $router->post("ps/{psId:$nationalIdRegex}/professions", ['uses' => 'ProfessionController@store']);
    $router->get("ps/{psId:$nationalIdRegex}/professions/{exProId}", ['uses' => 'ProfessionController@show']);
    $router->put("ps/{psId:$nationalIdRegex}/professions/{exProId}", ['uses' => 'ProfessionController@update']);
    $router->delete("ps/{psId:$nationalIdRegex}/professions/{exProId}", ['uses' => 'ProfessionController@destroy']);

    $router->get("ps/{psId:$nationalIdRegex}/professions/{exProId}/expertises", ['uses' => 'ExpertiseController@index']);
    $router->post("ps/{psId:$nationalIdRegex}/professions/{exProId}/expertises", ['uses' => 'ExpertiseController@store']);
    $router->get("ps/{psId:$nationalIdRegex}/professions/{exProId}/expertises/{expertiseId}", ['uses' => 'ExpertiseController@show']);
    $router->put("ps/{psId:$nationalIdRegex}/professions/{exProId}/expertises/{expertiseId}", ['uses' => 'ExpertiseController@update']);
    $router->delete("ps/{psId:$nationalIdRegex}/professions/{exProId}/expertises/{expertiseId}", ['uses' => 'ExpertiseController@destroy']);

    $router->get("ps/{psId:$nationalIdRegex}/professions/{exProId}/situations", ['uses' => 'WorkSituationController@index']);
    $router->post("ps/{psId:$nationalIdRegex}/professions/{exProId}/situations", ['uses' => 'WorkSituationController@store']);
    $router->get("ps/{psId:$nationalIdRegex}/professions/{exProId}/situations/{situId}", ['uses' => 'WorkSituationController@show']);
    $router->put("ps/{psId:$nationalIdRegex}/professions/{exProId}/situations/{situId}", ['uses' => 'WorkSituationController@update']);
    $router->delete("ps/{psId:$nationalIdRegex}/professions/{exProId}/situations/{situId}", ['uses' => 'WorkSituationController@destroy']);

    $router->get('structures',  ['uses' => 'StructureController@index']);
    $router->post('structures',  ['uses' => 'StructureController@store']);
    $router->get("structures/{structureId}", ['uses' => 'StructureController@show']);
    $router->put("structures/{structureId}", ['uses' => 'StructureController@update']);
    $router->delete("structures/{structureId}", ['uses' => 'StructureController@destroy']);
});
