<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 *
 * Copyright (c) HRADigital - Hugo Rafael Azevedo.
 */

declare(strict_types=1);

namespace HraDigital\Components\DatatypesDoctrine\Types;

use Doctrine\DBAL\Types\Type;

/**
 * Common base for every Doctrine Type shipped by this package.
 *
 * Carries the type's registered NAME and the DBAL 3 / DBAL 4 compatibility shim, so each
 * concrete Type only has to describe its own column and its own two conversions.
 *
 * @package   HraDigital\Components\DatatypesDoctrine
 * @copyright HraDigital\Components\DatatypesDoctrine
 * @license   MPL-2.0
 */
abstract class AbstractDatatypeType extends Type
{
    /**
     * The name the Type is registered under, and the name an Entity's mapping refers to.
     *
     * Concrete Types override this. The default type names can be changed in the published
     * `datatypes-doctrine.php` config, in which case a Type answers to the configured name -
     * this constant stays the package default.
     */
    public const NAME = '';

    /**
     * DBAL 3 declares `getName()` abstract on Type; DBAL 4 dropped it entirely. Declaring it
     * here keeps ONE code base valid on both majors - on DBAL 4 it is simply an extra method
     * nothing calls, on DBAL 3 it satisfies the abstract.
     */
    public function getName(): string
    {
        return static::NAME;
    }
}
