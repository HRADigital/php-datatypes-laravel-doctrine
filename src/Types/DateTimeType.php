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

use Doctrine\DBAL\Platforms\AbstractPlatform;
use HraDigital\Datatypes\Datetime\DateTime;

/**
 * Maps HraDigital\Datatypes\Datetime\DateTime onto a DATETIME column.
 *
 * @package   HraDigital\Components\DatatypesDoctrine
 * @copyright HraDigital\Components\DatatypesDoctrine
 * @license   MPL-2.0
 */
class DateTimeType extends AbstractDatetimeType
{
    public const NAME = 'DateTimeType';

    /** @inheritDoc */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getDateTimeTypeDeclarationSQL($column);
    }

    protected function datatypeClass(): string
    {
        return DateTime::class;
    }

    protected function formatString(AbstractPlatform $platform): string
    {
        return $platform->getDateTimeFormatString();
    }
}
