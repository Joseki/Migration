<?php

namespace Joseki\Migration\Generator\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;

class TimestampType extends Type
{
    const TIMESTAMP = 'timestamp';

    private $onUpdate = '';



    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::TIMESTAMP;
    }



    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        $fieldDeclaration['version'] = true;
        return $platform->getDateTimeTypeDeclarationSQL($fieldDeclaration);
    }



    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return ($value !== null) ? $value->format($platform->getTimeFormatString()) : null;
    }



    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null || $value instanceof \DateTime) {
            return $value;
        }

        $val = \DateTime::createFromFormat($platform->getTimeFormatString(), $value);
        if (!$val) {
            throw ConversionException::conversionFailedFormat($value, $this->getName(), $platform->getTimeFormatString());
        }

        return $val;
    }



    /**
     * @param bool $param
     */
    public function enableOnUpdate($param)
    {
        $this->onUpdate = (bool) $param ? ' ON UPDATE CURRENT_TIMESTAMP' : '';
    }

}
