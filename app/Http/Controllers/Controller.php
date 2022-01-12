<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @SWG\Swagger(
 *   basePath="",
 *   schemes= {"http", "https"},
 *   host = L5_SWAGGER_CONST_HOST,
 *   @QA\Info(
 *     version="3.0",
 *     title="Swagger Integration with PHP Laravel",
 *     description="Integrate Swagger in Laravel application",
 *   @SWG\Contact(
 *          email="afrozsatvilkar2014@gmail.com"
 *     ),
 *   )
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
