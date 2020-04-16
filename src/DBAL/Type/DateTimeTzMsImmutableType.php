<?php

declare(strict_types=1);

namespace CoreExtensions\Doctrine\DBAL\Type;

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
        if (!\is_object($value)) {
            return parent::convertToDatabaseValue($value, $platform);
        }

        /**
         * Some platform by default save datetime without microseconds
         */
        switch (true) {
            case $platform instanceof PostgreSqlPlatform:
            case $platform instanceof MySQL57Platform:
                $dateTimeFormat = $platform->getDateTimeTzFormatString();

                return $value->format("{$dateTimeFormat}.u");
        }
    }
}
