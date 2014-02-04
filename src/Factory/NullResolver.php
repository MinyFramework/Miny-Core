<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Factory;

class NullResolver extends AbstractLinkResolver
{

    /**
     * @inheritdoc
     */
    public function resolveReferences($argument)
    {
        return $argument;
    }
}
