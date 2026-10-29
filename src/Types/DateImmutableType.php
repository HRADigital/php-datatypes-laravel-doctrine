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
 * Maps HraDigital\Datatypes\Datetime\DateTimeImmutable onto a DATE column.
 *
 * The time part resets to midnight on read, the same as DateType.
 *
 * @package   HraDigital\Components\DatatypesDoctrine
 * @copyright HraDigital\Components\DatatypesDoctrine
 * @license   MPL-2.0
 */
class DateImmutableType extends DateType
{
    public const NAME = 'DateImmutableType';

    protected function datatypeClass(): string
    {
        return DateTimeImmutable::class;
    }
}
