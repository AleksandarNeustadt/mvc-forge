<?php

namespace App\Controllers;

/**
 * Public API user route adapter.
 */
class ApiUserController extends ApiController
{
}


if (!\class_exists('ApiUserController', false) && !\interface_exists('ApiUserController', false) && !\trait_exists('ApiUserController', false)) {
    \class_alias(__NAMESPACE__ . '\\ApiUserController', 'ApiUserController');
}
