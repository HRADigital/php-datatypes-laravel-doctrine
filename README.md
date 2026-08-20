# php-datatypes-laravel-doctrine

[![CI](https://github.com/HRADigital/php-datatypes-laravel-doctrine/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/HRADigital/php-datatypes-laravel-doctrine/actions/workflows/ci.yml)
[![Release](https://github.com/HRADigital/php-datatypes-laravel-doctrine/actions/workflows/release.yml/badge.svg?branch=master)](https://github.com/HRADigital/php-datatypes-laravel-doctrine/actions/workflows/release.yml)
[![Latest Release](https://img.shields.io/github/v/release/HRADigital/php-datatypes-laravel-doctrine)](https://github.com/HRADigital/php-datatypes-laravel-doctrine/releases)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/hradigital/php-datatypes-laravel-doctrine)](https://packagist.org/packages/hradigital/php-datatypes-laravel-doctrine)
[![Total Downloads](https://img.shields.io/packagist/dt/hradigital/php-datatypes-laravel-doctrine)](https://packagist.org/packages/hradigital/php-datatypes-laravel-doctrine)
[![PHP Version](https://img.shields.io/packagist/php-v/hradigital/php-datatypes-laravel-doctrine)](https://packagist.org/packages/hradigital/php-datatypes-laravel-doctrine)
[![Laravel](https://img.shields.io/badge/Laravel-12%20%7C%2013-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%206-brightgreen)](phpstan.neon.dist)
[![Code Style](https://img.shields.io/badge/code%20style-PSR--12-blue)](phpcs.xml.dist)
[![License](https://img.shields.io/github/license/HRADigital/php-datatypes-laravel-doctrine)](LICENSE)
[![Last Commit](https://img.shields.io/github/last-commit/HRADigital/php-datatypes-laravel-doctrine)](https://github.com/HRADigital/php-datatypes-laravel-doctrine/commits/master)
[![Open Issues](https://img.shields.io/github/issues/HRADigital/php-datatypes-laravel-doctrine)](https://github.com/HRADigital/php-datatypes-laravel-doctrine/issues)
[![Contributors](https://img.shields.io/github/contributors/HRADigital/php-datatypes-laravel-doctrine)](https://github.com/HRADigital/php-datatypes-laravel-doctrine/graphs/contributors)
[![Stars](https://img.shields.io/github/stars/HRADigital/php-datatypes-laravel-doctrine)](https://github.com/HRADigital/php-datatypes-laravel-doctrine/stargazers)
[![Code Size](https://img.shields.io/github/languages/code-size/HRADigital/php-datatypes-laravel-doctrine)](https://github.com/HRADigital/php-datatypes-laravel-doctrine)
[![Conventional Commits](https://img.shields.io/badge/Conventional%20Commits-1.0.0-FE5196?logo=conventionalcommits&logoColor=white)](https://conventionalcommits.org)

This package lets a `hradigital/php-datatypes` object be the declared type of a Doctrine Entity
attribute. Doctrine persists only the types it ships with, so an `EmailAddress`, a `Money` or an
`Address` would otherwise have to be declared as a primitive and converted by hand on every read and
write. It ships one Doctrine DBAL Type per Datatypes object, plus a Laravel Service Provider that
registers them all, so the Entity holds the Value Object while the database still holds a plain,
indexable, human-readable value.

- **Doctrine DBAL Types** - one per Datatypes object: `StrType`, `EmailAddressType`, `UrlType`,
  `SlugType`, `DatetimeType`, `CurrencyType`, `MoneyType`, `AddressType` and the rest.
- **Native PHP Types** - `NativeDateTimeType`, `NativeDateTimeImmutableType`,
  `NativeDateIntervalType` and `NativeDateTimeZoneType`, so an Entity holding a plain `\DateTime`
  maps under the same naming convention.
- **Laravel Service Provider** - auto-discovered, registering every Type before any EntityManager
  resolves a mapping. Nothing to publish, nothing to configure.
- **One naming rule** - a Doctrine type name is always the Type class' own short name.
- **Validation on the way in** - a Value Object's validation failure surfaces as a DBAL
  `ConversionException`, so an invalid value is rejected rather than corrupting the column.
- **Framework-optional** - the Types are plain DBAL Types with no framework dependency; the Service
  Provider is a convenience.

## What this package is not

- **Not a fork or a replacement** for `hradigital/php-datatypes`. It depends on it and adds
  persistence, nothing more. The Value Objects' behaviour, validation and API are entirely theirs.
- **Not an ORM, and not a set of Entities.** It contributes column types. Your Entities, mappings
  and repositories stay yours.
- **Not Laravel-only.** See [Using it outside Laravel](#using-it-outside-laravel).

## Requirements

| Requirement | Supported |
|---|---|
| PHP | 8.2, 8.3, 8.4 |
| `hradigital/php-datatypes` | ^3.0 |
| `doctrine/dbal` | ^3.8, ^4.0 |
| Laravel (`illuminate/*`) | 12, 13 |

Laravel 13 itself requires PHP 8.3 or newer; on PHP 8.2 the package runs on Laravel 12.

Both DBAL majors are supported from one code base and both are exercised on every CI run.

A Doctrine ORM integration for Laravel - typically
[`laravel-doctrine/orm`](https://github.com/laravel-doctrine/orm) - is what gives your application
Entities in the first place. This package does not require it: it registers into Doctrine's own
global Type registry, which every integration reads from.

## Zero configuration

Install it and the Types are usable. There is **nothing to publish, nothing to add to
`config/app.php`, and nothing to add to your Doctrine config** - Laravel's package auto-discovery
finds the Service Provider, and the Service Provider registers every Type before any EntityManager
resolves a mapping.

```bash
composer require hradigital/php-datatypes-laravel-doctrine
```

That is the whole installation. Skip straight to [Usage](#usage).

## At a glance

```php
// Without this package - the Entity holds a primitive, and every caller converts by hand.
#[ORM\Column(type: 'string', length: 255)]
private ?string $email = null;

// With this package - the Entity holds the Value Object, validated and normalized.
#[ORM\Column(type: 'EmailAddressType', nullable: true)]
private ?EmailAddress $email = null;
```

Nothing else changes. `$user->getEmail()` now hands you an `EmailAddress`, with its
`getUsername()` / `getDomain()` / `getTld()` accessors and its validation, and the database still
holds a plain, indexable, human-readable `VARCHAR`.

## Usage

Declare the attribute with the Value Object's own type, and map the column with the matching
Doctrine type name.

### Attributes

```php
use Doctrine\ORM\Mapping as ORM;
use HraDigital\Datatypes\Datetime\Datetime;
use HraDigital\Datatypes\Financial\Money;
use HraDigital\Datatypes\ValueObjects\Address;
use HraDigital\Datatypes\Web\EmailAddress;
use HraDigital\Datatypes\Web\Seo\Slug;

#[ORM\Entity]
#[ORM\Table(name: 'customers')]
class Customer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'EmailAddressType', length: 255, unique: true)]
    private EmailAddress $email;

    #[ORM\Column(type: 'SlugType', unique: true)]
    private Slug $slug;

    #[ORM\Column(type: 'AddressType', nullable: true)]
    private ?Address $billingAddress = null;

    #[ORM\Column(type: 'MoneyType')]
    private Money $lifetimeValue;

    #[ORM\Column(type: 'DatetimeType')]
    private Datetime $registeredAt;
}
```

### Annotations

```php
/**
 * @ORM\Column(type="DatetimeType", name="date_of_birth", nullable=true)
 */
private ?Datetime $dateOfBirth = null;

/**
 * @ORM\Column(type="NativeDateTimeType", name="last_login_at", nullable=true)
 */
private ?DateTime $lastLoginAt = null;
```

### The naming rule

**A Doctrine type name is always the Type class' own short name.** An attribute declared as
`Money $total` maps with `type: 'MoneyType'`; one declared as `Slug $slug` maps with
`type: 'SlugType'`. There is no prefix to remember and no lookup table to consult - if you know the
Value Object, you know the mapping.

The four Types covering **native PHP** classes carry a `Native` prefix, because three of them would
otherwise collide with a Datatypes class: PHP's `\DateInterval` and `\DateTimeZone` share their
short name with the Datatypes classes outright, and `\DateTime` differs from Datatypes' `Datetime`
by a single capital letter. Without the prefix, a mapping would be one keystroke away from silently
selecting the wrong Type.

## Type reference

### Datatypes library

| Doctrine type | PHP type on the Entity | Column | Notes |
|---|---|---|---|
| `StrType` | `HraDigital\Datatypes\Scalar\Str` | `VARCHAR(255)` | Width overridable via `length`. Accepts a native `string` on write. |
| `EmailAddressType` | `…\Web\EmailAddress` | `VARCHAR(255)` | Stores the case-normalized address, so equal addresses compare equal in SQL. |
| `UrlType` | `…\Web\Url` | `VARCHAR(2048)` | Stores the normalized URL. See [Indexing a URL](#indexing-a-url). |
| `SlugType` | `…\Web\Seo\Slug` | `VARCHAR(191)` | 191, not 255 - the widest utf8mb4 column InnoDB accepts in a single-column UNIQUE index. |
| `DatetimeType` | `…\Datetime\Datetime` | `DATETIME` | |
| `DatetimeTzType` | `…\Datetime\Datetime` | `DATETIME` / `TIMESTAMPTZ` | Only some platforms store the offset. See [Timezones](#timezones). |
| `DateType` | `…\Datetime\Datetime` | `DATE` | Time resets to midnight on read. |
| `TimeType` | `…\Datetime\Datetime` | `TIME` | Date resets to the epoch on read. |
| `DateIntervalType` | `…\Datetime\DateInterval` | `VARCHAR(64)` | ISO-8601 duration, `-` prefixed when inverted. |
| `DateTimeZoneType` | `…\Datetime\DateTimeZone` | `VARCHAR(64)` | IANA identifier, never a fixed offset. |
| `CurrencyType` | `…\Financial\Currency` | `CHAR(3)` | ISO-4217 code. An unknown code fails rather than loading as `null`. |
| `MoneyType` | `…\Financial\Money` | `JSON` | `{"amount": <minor units, integer>, "currency": "<code>"}`. See [Multi-field Value Objects](#multi-field-value-objects). |
| `AddressType` | `…\ValueObjects\Address` | `JSON` | `{"street", "postal_code", "city", "country"}`. See [Multi-field Value Objects](#multi-field-value-objects). |

### Native PHP classes

Provided so an Entity holding a plain `\DateTime` maps with the same naming convention as one
holding a Datatype. Behaviour matches Doctrine's own built-ins; these are a naming convenience, not
a different conversion.

| Doctrine type | PHP type on the Entity | Column | Doctrine's own equivalent |
|---|---|---|---|
| `NativeDateTimeType` | `\DateTime` | `DATETIME` | `datetime` |
| `NativeDateTimeImmutableType` | `\DateTimeImmutable` | `DATETIME` | `datetime_immutable` |
| `NativeDateIntervalType` | `\DateInterval` | `VARCHAR(64)` | `dateinterval` |
| `NativeDateTimeZoneType` | `\DateTimeZone` | `VARCHAR(64)` | - |

### Null handling

Every Type passes `null` through untouched in both directions. A nullable attribute needs nothing
beyond Doctrine's own `nullable: true`.

### Invalid data

Every Type routes the Value Object's own validation failure into a
`Doctrine\DBAL\Types\ConversionException`, so a malformed row surfaces as the DBAL error your
application already handles - and an invalid value is rejected on the way *in* rather than
corrupting the column. An unknown currency code, a relative URL, a slug with spaces and an unknown
timezone identifier all fail loudly.

## Datatypes deliberately without a Type

Not every object in the Datatypes library is a *column*. These have no Type, on purpose:

| Object | Why not |
|---|---|
| `ValueObjects\Pagination` | A request-scoped query parameter, not entity state. It is never persisted. |
| `Collections\*` (`Store`, `Queue`, `Stack`, `EntityCollection`) | Collections of entities or values. Doctrine models these as associations or as a `json` column of primitives - a Type would hide the relational structure. |
| `Exceptions\*` | Control flow, not data. |
| `Attributes\*` traits, `ValueObjects\Traits\*` | Traits that shape a class' behaviour; they have no value of their own to store. |
| `ValueObjects\AbstractValueObject` | Abstract - the concrete Value Objects that extend it get their own mapping. |

## Multi-field Value Objects

`Money` and `Address` carry more than one field, so they have no single scalar form. This package
maps each of them to **one JSON column**, which keeps the Value Object mappable as one Entity
attribute - the whole point of the package.

That is not the only sensible mapping, and it is not always the right one. Both Value Objects are
explicitly designed to be spread over one column per field:

- `Address` has `toArray()` / `fromArray()` over `street`, `postal_code`, `city`, `country`.
- `Money` has `fromScalars(int $amount, string $currency)` over a `BIGINT` plus the currency code.

**Use the JSON Type when** the Value Object is stored and read as a whole.

**Use a Doctrine Embeddable over separate columns when** the individual fields have to be queried,
indexed, sorted or aggregated in SQL - summing order totals, filtering customers by city, putting a
`UNIQUE` index on a postal code. A JSON column can do none of those efficiently on every platform.

```php
// Money as two real columns - sortable, indexable, summable.
#[ORM\Column(type: 'bigint')]
private int $totalAmount;

#[ORM\Column(type: 'CurrencyType')]
private Currency $totalCurrency;

public function getTotal(): Money
{
    return Money::fromScalars($this->totalAmount, $this->totalCurrency->value);
}
```

Note that `CurrencyType` is useful on its own in exactly that split mapping.

## Configuration (optional)

You do not need this. It exists for the two cases where the defaults do not fit: renaming a Type
whose name clashes with another package's, and turning a Type off.

```bash
php artisan vendor:publish --tag=datatypes-doctrine-config
```

That writes `config/datatypes-doctrine.php`:

```php
return [
    // Turns the whole package off without uninstalling it.
    'enabled' => true,

    // Doctrine type name => Type class.
    'types' => DoctrineTypes::defaults(),
];
```

Rename a Type by changing its key; drop one by removing its entry.

**A name already claimed by another package is never overwritten.** The first registration wins and
this package steps aside, because silently swapping another package's Type would corrupt its
columns. Renaming here is the supported way to resolve a clash.

## Platform notes

### Timezones

`DatetimeTzType` stores the UTC offset only on platforms that have a timezone-aware column type -
PostgreSQL `TIMESTAMP WITH TIME ZONE`, Oracle, SQL Server `DATETIMEOFFSET`. **MySQL has no such
column type**, and DBAL falls back to a plain `DATETIME`, which silently drops the offset. On MySQL,
prefer `DatetimeType` plus a separate timezone column mapped with `DateTimeZoneType`.

### Indexing a URL

A URL routinely overflows what a database engine will index - InnoDB caps an index key at 3072
bytes, which a utf8mb4 `VARCHAR(2048)` blows straight past. When the column has to be unique, index
`Url::getHash()` in a second, fixed-width column rather than widening the first one:

```php
#[ORM\Column(type: 'UrlType')]
private Url $url;

#[ORM\Column(type: 'string', length: 32, unique: true)]
private string $urlHash;   // populated from $this->url->getHash()
```

### JSON columns across DBAL majors

The same MySQL platform renders a JSON column as `LONGTEXT` on DBAL 3 and as `JSON` on DBAL 4. Both
are correct for their major; the stored document is identical either way.

## Using it outside Laravel

The Types are plain `Doctrine\DBAL\Types\Type` subclasses with no framework dependency. Register
them yourself:

```php
use Doctrine\DBAL\Types\Type;
use HraDigital\Components\DatatypesDoctrine\DoctrineTypes;

foreach (DoctrineTypes::defaults() as $name => $class) {
    if (! Type::hasType($name)) {
        Type::addType($name, $class);
    }
}
```

`DoctrineTypes::datatypes()` and `DoctrineTypes::natives()` return the two halves separately, if you
want only one of them.

## Development

Every gate runs through the `Makefile`, so the command a developer runs locally is the command CI
runs. `make help` lists them all.

```bash
make validate                 # every static gate, concurrently
make validate-implementation  # serial pre-merge pipeline, stops at the first failure
make test                     # the full PHPUnit suite
make test-unit                # the Types themselves, against a real DBAL platform
make test-feature             # the Laravel wiring, booted through orchestra/testbench
make lint / lint-fix          # PHPCS / PHPCBF
make cs-fixer / cs-fixer-fix  # PHP-CS-Fixer, import style only
make analyse                  # PHPStan, level 6
```

Append `QUIET=1` to any target for silent-on-success output. Scope the file-based gates with
`FILES="a.php b.php"`, and narrow a test run with `FILTER=MoneyType`.

### Git hooks

```bash
make hooks-install
```

Points git at the repo's version-controlled `.githooks/`:

- **`commit-msg`** - rejects a commit message that is not a Conventional Commit. The package's
  version is derived entirely from commit messages, so a malformed one silently costs a release,
  and by the time CI catches it the message is already in the history.
- **`pre-push`** - runs the same static gates CI runs, before anything leaves the machine.

Hooks are per-clone and opt-in, which is exactly why CI enforces the same rules independently.

### Commit messages and releases

Releases are automatic and derived from the commit history. On a green CI run against `master`, the
release workflow computes the next version and cuts a tag with generated notes.

| Commit type | Effect |
|---|---|
| `BREAKING CHANGE:` footer | major |
| `feat` | minor |
| everything else, including `build`, `chore`, `ci`, `docs`, `fix`, `perf`, `refactor`, `revert`, `style`, `test` | patch |

Every commit that lands on `master` ships a version - the version identifies the state of the
project, so there is no "no release" bucket. A breaking change needs the **footer**, on its own line
after a blank line; a `feat!:` suffix alone does **not** cut a major.

## Licence

Released under the **Mozilla Public License 2.0**. See [LICENSE](LICENSE).

MPL-2.0 is file-level copyleft: you may use, modify and distribute this package inside closed-source
and commercial products, and you only have to publish your changes to *this package's own files*.

The licence covers the code. It grants no rights in the HRADigital names or logos - see
[TRADEMARK.md](TRADEMARK.md). Dependencies carry their own licences.

Copyright (c) HRADigital - Hugo Rafael Azevedo.
