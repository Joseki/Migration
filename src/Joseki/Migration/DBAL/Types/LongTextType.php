<?php


namespace Joseki\Migration\DBAL\Types;

class LongTextType extends \Doctrine\DBAL\Types\TextType
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
