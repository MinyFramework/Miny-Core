<?php

namespace Miny\Factory;

class NullResolver extends AbstractLinkResolver
{

    public function resolveReferences($argument)
    {
        return $argument;
    }
}
