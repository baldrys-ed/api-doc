<?php

namespace Dev\ApiDocBundle\Describer;

use Dev\ApiDocBundle\Model\Model;

interface DescriberInterface
{
    /**
     * Creates model and fill it with data by using attribute parsers
     * @param string $class
     * @return Model
     */
    public function describe(string $class): Model;
}