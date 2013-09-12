<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Miny\View;

interface iTemplatingView
{
    public function __construct($template);
    public function setHelpers(ViewHelpers $helpers);
    public function setTemplate($template);
    public function getTemplate();
}