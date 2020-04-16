<?php

declare(strict_types=1);

namespace CoreExtensions\Doctrine\DBAL\Type;

use DateTimeImmutable;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQL57Platform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DateTimeTzImmutableType;

/**
 * Override datetime datatype to support microseconds
 *
 * @see https://github.com/doctrine/dbal/issues/2873
 * @see https://blog.tomhanderson.com/2018/09/datetime-with-microseconds-for-mysql-in.html
 */
class DateTimeTzMsImmutableType extends DateTimeTzImmutableType
{
    private const FORMAT = 'Y-m-d H:i:s.uP';
    private const FORMAT_WITH_ZERO_MS = 'Y-m-d H:i:sP';

    /**
     * @param array            $fieldDeclaration
     * @param AbstractPlatform $platform
     * @return string
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        /**
         * Some platform by default save datetime without microseconds
         */
        switch (true) {
            case $platform instanceof PostgreSqlPlatform:
                return 'TIMESTAMP WITH TIME ZONE';
        }

        return parent::getSQLDeclaration($fieldDeclaration, $platform);
    }


    /**
     * @param                  $value
     * @param AbstractPlatform $platform
     * @return null|string
     * @throws ConversionException
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (!\is_object($value) || !($value instanceof DateTimeImmutable)) {
            return parent::convertToDatabaseValue($value, $platform);
        }

        /**
         * Some platform by default save datetime without microseconds
         */
        switch (true) {
            case $platform instanceof PostgreSqlPlatform:
            case $platform instanceof MySQL57Platform:
                return $value->format(static::FORMAT);
        }
    }

    /**
     * @param                  $value
     * @param AbstractPlatform $platform
     * @return bool|\DateTime|DateTimeImmutable|mixed|null
     * @throws ConversionException
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null || $value instanceof DateTimeImmutable) {
            return $value;
        }

        $dateTime = null;

        /**
         * Some platform by default save datetime without microseconds
         */
        switch (true) {
            case $platform instanceof PostgreSqlPlatform:
            case $platform instanceof MySQL57Platform:
                $dateTime = DateTimeImmutable::createFromFormat(static::FORMAT, $value);
                break;
        }

        if (false === $dateTime) {
            /**
             * if the date has zero in microseconds part
             */
            $dateTime = DateTimeImmutable::createFromFormat($platform->getDateTimeTzFormatString(), $value);
        }

        if (!$dateTime) {
            throw ConversionException::conversionFailedFormat(
                $value,
                $this->getName(),
                static::FORMAT
            );
        }

        return $dateTime;
    }


}
