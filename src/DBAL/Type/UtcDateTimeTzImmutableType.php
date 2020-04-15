<?php

declare(strict_types=1);

namespace CoreExtensions\DoctrineExtensions\DBAL\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DateTimeTzImmutableType;

/**
 * @see https://www.doctrine-project.org/projects/doctrine-orm/en/2.7/cookbook/working-with-datetime.html
 */
class UtcDateTimeTzImmutableType extends DateTimeTzImmutableType
{
    /**
     * @var \DateTimeZone
     */
    private static $utc;

    /**
     * @param                  $value
     * @param AbstractPlatform $platform
     * @return mixed|string
     * @throws ConversionException
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value instanceof \DateTimeImmutable) {
            $value = $value->setTimezone(self::getUtc());
        }

        return parent::convertToDatabaseValue($value, $platform);
    }

    /**
     * @param                  $value
     * @param AbstractPlatform $platform
     * @return bool|\DateTime|\DateTimeImmutable|mixed
     * @throws ConversionException
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (null === $value || $value instanceof \DateTimeImmutable) {
            return $value;
        }

        $converted = \DateTimeImmutable::createFromFormat(
            $platform->getDateTimeFormatString(),
            $value,
            self::getUtc()
        );

        if (!$converted) {
            throw ConversionException::conversionFailedFormat(
                $value,
                $this->getName(),
                $platform->getDateTimeFormatString()
            );
        }

        $converted = $converted->setTimezone(static::getUtc());

        return $converted;
    }

    private static function getUtc(): \DateTimeZone
    {
        return self::$utc ?: self::$utc = new \DateTimeZone('UTC');
    }
}
