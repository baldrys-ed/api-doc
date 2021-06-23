<?php

namespace Dev\ApiDocBundle\Model;

class Component extends Model
{
    public Component|Property|null $parent = null;
    /**
     * Parameters for component in associative array
     * @var array
     */
    public array $parameters = [];
    public ?int $status = null;
    public array $headers = [];
    public array $required = [];
}