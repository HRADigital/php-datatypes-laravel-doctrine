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
 * Maps HraDigital\Datatypes\Datetime\Datetime onto a DATE column.
 *
 * The time part resets to midnight on read, so the same stored date always rehydrates to the
 * same instant - see AbstractDatetimeType::resetsUnparsedFields().
 *
 * @package   HraDigital\Components\DatatypesDoctrine
 * @copyright HraDigital\Components\DatatypesDoctrine
 * @license   MPL-2.0
 */
class DateType extends AbstractDatetimeType
{
    public const NAME = 'DateType';

    /** @inheritDoc */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getDateTypeDeclarationSQL($column);
    }

    protected function formatString(AbstractPlatform $platform): string
    {
        return $platform->getDateFormatString();
    }

    protected function resetsUnparsedFields(): bool
    {
        return true;
    }
}
