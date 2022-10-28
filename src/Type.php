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

    public static function gettypeEquivalent(Type $type): string
    {
        return match ($type) {
            Type::ARRAY => 'array',
            Type::INT => 'integer',
            Type::BOOL => 'boolean',
            Type::FLOAT => 'double',
            Type::STRING => 'string',
            Type::STD_CLASS => 'object',
            Type::CALLABLE => 'callable',
            Type::RESOURCE => 'resource',
        };
    }
}
