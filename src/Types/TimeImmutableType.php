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
 * Maps HraDigital\Datatypes\Datetime\DateTimeImmutable onto a TIME column.
 *
 * The date part resets to the epoch on read, the same as TimeType.
 *
 * @package   HraDigital\Components\DatatypesDoctrine
 * @copyright HraDigital\Components\DatatypesDoctrine
 * @license   MPL-2.0
 */
class TimeImmutableType extends TimeType
{
    public const NAME = 'TimeImmutableType';

    protected function datatypeClass(): string
    {
        return DateTimeImmutable::class;
    }
}
