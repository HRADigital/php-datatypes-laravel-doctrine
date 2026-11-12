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
use HraDigital\Datatypes\ValueObjects\RecordId;

/**
 * Maps HraDigital\Datatypes\ValueObjects\RecordId onto the platform's GUID column - CHAR(36) on
 * MySQL, UUID on PostgreSQL.
 *
 * Stored as the canonical lower-case UUID string, so the column stays human-readable. A UUID
 * of any version other than 7 fails on both write and read.
 *
 * @package   HraDigital\Components\DatatypesDoctrine
 * @copyright HraDigital\Components\DatatypesDoctrine
 * @license   MPL-2.0
 */
class RecordIdStringType extends AbstractRecordIdType
{
    public const NAME = 'RecordIdStringType';

    /** @inheritDoc */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getGuidTypeDeclarationSQL($column);
    }

    protected function toColumn(RecordId $id): string
    {
        return (string) $id;
    }

    protected function fromColumn(string $value): RecordId
    {
        return $this->datatypeClass()::fromString($value);
    }
}
