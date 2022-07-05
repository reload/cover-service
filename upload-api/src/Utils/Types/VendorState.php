<?php

namespace App\Utils\Types;

class VendorState
{
    final public const INSERT = 'insert';
    final public const UPDATE = 'update';
    final public const DELETE = 'delete';
    final public const DELETE_AND_UPDATE = 'deleteAndUpdate';
}
