<?php

namespace Reed\Core;

interface ItemInterface
{
    function getUID(): string;
    function getId(): string;
    function getParent(): ?ItemInterface;
    // function setParent(ElementInterface $parent) : void;
    function getType(): string;
}
