<?php

namespace Miny\Widget;

class WidgetContainer {

    private $widgets = array();
    private $groups;
    private $factory;

    public function __construct(\Miny\Factory\Factory $factory) {
        $this->groups = array('no_group' => array());
        $this->factory = $factory;
    }

    public function addGroup($group, array $widgets = array()) {
        if (!isset($this->groups[$group])) {
            $this->groups[$group] = $widgets;
        }
    }

    public function removeGroup($group) {
        unset($this->groups[$group]);
    }

    public function renderGroup($group) {
        $output = '';
        if (isset($this->groups[$group])) {
            foreach ($this->groups[$group] as $widget_id) {
                $output .= $this->renderWidget($widget_id);
            }
        }
        return $output;
    }

    public function addWidget($id, $name = NULL, array $parameters = array()) {
        $this->widgets[$id] = array($name ? : $id, $parameters);
    }

    public function removeWidget($id, $group = 'no_group') {
        unset($this->groups[$group][$id]);
        unset($this->widgets[$id]);
    }

    public function widgetExists($id) {
        return isset($this->widgets[$id]);
    }

    public function getWidgetParameters($id) {
        if (!isset($this->widgets[$id])) {
            throw new \OutOfBoundsException('Widget not set: ' . $id);
        }
        return $this->widgets[$id][1];
    }

    public function getWidget($name, array $parameters = array()) {
        $widget_name = $name . '_widget';
        $widget = $this->factory->$widget_name;
        if (!$widget instanceof iWidget) {
            throw new \InvalidArgumentException('Invalid widget: ' . $widget_name . '(' . get_class($widget) . ')');
        }
        return $widget->begin($parameters);
    }

    public function renderWidget($id, array $parameters = array()) {
        if (isset($this->widgets[$id])) {
            $parameters = $parameters + $this->getWidgetParameters($id);
            $id = $this->widgets[$id][0];
        }
        return $this->getWidget($id)->end($parameters);
    }
//
//    public function render(iWidget $widget) {
//
//    }

}