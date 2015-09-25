<?php


namespace Joseki\Migration\Generator\DBAL\Types;

use Doctrine\DBAL\Types\TextType;

class LongTextType extends TextType
{
    const LONG_TEXT = 'longtext';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::LONG_TEXT;
    }
}
