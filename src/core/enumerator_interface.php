<?php
namespace Reed\Core;

interface EnumeratorInterface 
{
    public static function enum(int $value);
    public function getValue();
}