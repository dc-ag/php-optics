<?php
declare(strict_types=1);

namespace dcAG\phpOptics;

enum Type: string
{
    case ARRAY = 'array';
    case INT = 'int';
    case BOOL = 'bool';
    case FLOAT = 'float';
    case STRING = 'string';
    case STD_CLASS = 'stdClass';
    case CALLABLE = 'callable';
    case RESOURCE = 'resource';

}
