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

use HraDigital\Datatypes\Datetime\DateTimeImmutable;

/**
 * Maps HraDigital\Datatypes\Datetime\DateTimeImmutable onto a timezone-aware DATETIME column.
 *
 * The same platform caveat as DateTimeTzType applies: MySQL has no timezone-aware column, and
 * the offset is NOT stored there.
 *
 * @package   HraDigital\Components\DatatypesDoctrine
 * @copyright HraDigital\Components\DatatypesDoctrine
 * @license   MPL-2.0
 */
class DateTimeTzImmutableType extends DateTimeTzType
{
    public const NAME = 'DateTimeTzImmutableType';

    protected function datatypeClass(): string
    {
        return DateTimeImmutable::class;
    }
}
