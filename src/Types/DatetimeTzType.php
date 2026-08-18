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

/**
 * Maps HraDigital\Datatypes\Datetime\Datetime onto a timezone-aware DATETIME column.
 *
 * Only some platforms carry the offset in the column itself (PostgreSQL TIMESTAMP WITH TIME
 * ZONE, Oracle, SQL Server DATETIMEOFFSET). On MySQL there is no such column type and DBAL
 * falls back to a plain DATETIME, which silently drops the offset - prefer DatetimeType plus
 * a separate timezone column there, mapped with DateTimeZoneType.
 *
 * @package   HraDigital\Components\DatatypesDoctrine
 * @copyright HraDigital\Components\DatatypesDoctrine
 * @license   MPL-2.0
 */
class DatetimeTzType extends AbstractDatetimeType
{
    public const NAME = 'DatetimeTzType';

    /** @inheritDoc */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getDateTimeTzTypeDeclarationSQL($column);
    }

    protected function formatString(AbstractPlatform $platform): string
    {
        return $platform->getDateTimeTzFormatString();
    }
}
