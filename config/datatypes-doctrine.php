<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 *
 * Copyright (c) HRADigital - Hugo Rafael Azevedo.
 */

declare(strict_types=1);

use HraDigital\Components\DatatypesDoctrine\DoctrineTypes;

/**
 * Nothing here needs publishing.
 *
 * The package merges this file into the application's config on register, so every Type below
 * is available to an Entity mapping straight after `composer require`. Publish it only to
 * rename a Type, or to drop one you do not want registered:
 *
 *   php artisan vendor:publish --tag=datatypes-doctrine-config
 */
return [

    /*
    |---------------------------------------------------------------------------
    | Registration switch
    |---------------------------------------------------------------------------
    |
    | Turns the whole package off without uninstalling it. Left on, the provider
    | registers every Type below with Doctrine's global Type registry, which is
    | what makes them usable in an Entity mapping with no further wiring.
    |
    */

    'enabled' => true,

    /*
    |---------------------------------------------------------------------------
    | Type map
    |---------------------------------------------------------------------------
    |
    | Doctrine type name => Type class. The name on the left is what an Entity's
    | mapping refers to, eg. #[ORM\Column(type: 'hra_email_address')].
    |
    | A name already claimed by another package is never overwritten - the first
    | registration wins and this package steps aside, so renaming here is the
    | supported way to resolve a clash.
    |
    */

    'types' => DoctrineTypes::defaults(),

];
