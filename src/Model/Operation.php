<?php

namespace Dev\ApiDocBundle\Model;

class Operation extends Model
{
    public ?string $path = '';
    public ?string $method = '';

    public ?string $description = '';
    public ?object $request = null;
    public array $security = [];
    public array $responses = [];

}