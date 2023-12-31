<?php

namespace App\Utils\Types;

class IdentifierType
{
    public const PID = 'pid';
    public const ISBN = 'isbn';
    public const ISSN = 'issn';
    public const ISMN = 'ismn';
    public const ISRC = 'isrc';
    public const FAUST = 'faust';

    /**
     * Get array of all defined identifier types.
     *
     * @return array
     *   An array of known identifiers. Uppercase identifier name in key, lower case identifier in value.
     *
     * @psalm-return array<string, mixed>
     */
    public static function getTypeList(): array
    {
        $oClass = new \ReflectionClass(__CLASS__);

        return $oClass->getConstants();
    }
}
