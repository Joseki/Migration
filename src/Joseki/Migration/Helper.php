<?php

namespace Joseki\Migration;

use Nette\Utils\Strings;

class Helper
{
    public static function format($name)
    {
        $name = Strings::webalize($name, null, false);
        return Strings::replace($name, '#-#', '_');
    }
}
