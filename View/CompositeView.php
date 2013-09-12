<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Miny\View;

class CompositeView implements iView
{
    protected $views = array();

    public function addView(iView $view)
    {
        $this->views[] = $view;
        return $this;
    }

    public function removeView(iView $view)
    {
        $key = array_search($view, $this->views, true);
        unset($this->views[$key]);
        return $this;
    }

    public function render()
    {
        $return = '';
        foreach ($this->views as $view) {
            $return .= $view->render();
        }
        return $return;
    }

}