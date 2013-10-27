<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Miny\View;

interface iView
{
    public function __construct($directory, $template);
    public function setHelpers(ViewHelpers $helpers);
    public function setVariables(array $variables);
    public function setTemplate($template);
    public function getTemplate();
    public function render();
}