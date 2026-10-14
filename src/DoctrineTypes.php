<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 *
 * Copyright (c) HRADigital - Hugo Rafael Azevedo.
 */

declare(strict_types=1);

namespace HraDigital\Components\DatatypesDoctrine;

use HraDigital\Components\DatatypesDoctrine\Types\AddressType;
use HraDigital\Components\DatatypesDoctrine\Types\CurrencyType;
use HraDigital\Components\DatatypesDoctrine\Types\DateIntervalType;
use HraDigital\Components\DatatypesDoctrine\Types\DatetimeType;
use HraDigital\Components\DatatypesDoctrine\Types\DatetimeTzType;
use HraDigital\Components\DatatypesDoctrine\Types\DateTimeZoneType;
use HraDigital\Components\DatatypesDoctrine\Types\DateType;
use HraDigital\Components\DatatypesDoctrine\Types\EmailAddressType;
use HraDigital\Components\DatatypesDoctrine\Types\MoneyType;
use HraDigital\Components\DatatypesDoctrine\Types\NativeDateIntervalType;
use HraDigital\Components\DatatypesDoctrine\Types\NativeDateTimeImmutableType;
use HraDigital\Components\DatatypesDoctrine\Types\NativeDateTimeType;
use HraDigital\Components\DatatypesDoctrine\Types\NativeDateTimeZoneType;
use HraDigital\Components\DatatypesDoctrine\Types\NifType;
use HraDigital\Components\DatatypesDoctrine\Types\NifTypeType;
use HraDigital\Components\DatatypesDoctrine\Types\SlugType;
use HraDigital\Components\DatatypesDoctrine\Types\StrType;
use HraDigital\Components\DatatypesDoctrine\Types\TimeType;
use HraDigital\Components\DatatypesDoctrine\Types\UrlType;

/**
 * The single source of truth for which Doctrine Types this package ships, and under which name.
 *
 * The shipped config file is generated from this map, and the ServiceProvider registers from
 * the config - so an application that publishes nothing still gets exactly this list, and one
 * that publishes the config can rename or drop entries without touching any other wiring.
 *
 * @package   HraDigital\Components\DatatypesDoctrine
 * @copyright HraDigital\Components\DatatypesDoctrine
 * @license   MPL-2.0
 */
final class DoctrineTypes
{
    /**
     * Every Type this package registers, keyed by the name an Entity mapping refers to.
     *
     * The name is always the Type class' own short name, so a mapping reads the same as the
     * class an attribute is declared with - `Money $total` maps with `type: 'MoneyType'`.
     *
     * @return array<string, class-string<Types\AbstractDatatypeType>>
     */
    public static function defaults(): array
    {
        return self::datatypes() + self::natives();
    }

    /**
     * The Types covering the hradigital/php-datatypes objects. This is the package's reason
     * to exist; the native ones below are the convenience that comes with it.
     *
     * @return array<string, class-string<Types\AbstractDatatypeType>>
     */
    public static function datatypes(): array
    {
        return [
            // Scalar.
            StrType::NAME          => StrType::class,

            // Web.
            EmailAddressType::NAME => EmailAddressType::class,
            UrlType::NAME          => UrlType::class,
            SlugType::NAME         => SlugType::class,

            // Date and time.
            DatetimeType::NAME     => DatetimeType::class,
            DatetimeTzType::NAME   => DatetimeTzType::class,
            DateType::NAME         => DateType::class,
            TimeType::NAME         => TimeType::class,
            DateIntervalType::NAME => DateIntervalType::class,
            DateTimeZoneType::NAME => DateTimeZoneType::class,

            // Financial.
            CurrencyType::NAME     => CurrencyType::class,
            MoneyType::NAME        => MoneyType::class,

            // Fiscal.
            NifType::NAME          => NifType::class,
            NifTypeType::NAME      => NifTypeType::class,

            // Value Objects.
            AddressType::NAME      => AddressType::class,
        ];
    }

    /**
     * The Types covering the native PHP classes, so an Entity that holds a plain \DateTime is
     * mapped with the same naming convention as one holding a Datatype.
     *
     * They carry a `Native` prefix because three of the four native classes share their short
     * name with a Datatypes class - \DateInterval and \DateTimeZone outright, \DateTime with
     * only a letter's difference from Datetime. Without the prefix a mapping would be one
     * capital letter away from silently selecting the wrong Type.
     *
     * @return array<string, class-string<Types\AbstractDatatypeType>>
     */
    public static function natives(): array
    {
        return [
            NativeDateTimeType::NAME          => NativeDateTimeType::class,
            NativeDateTimeImmutableType::NAME => NativeDateTimeImmutableType::class,
            NativeDateIntervalType::NAME      => NativeDateIntervalType::class,
            NativeDateTimeZoneType::NAME      => NativeDateTimeZoneType::class,
        ];
    }
}
