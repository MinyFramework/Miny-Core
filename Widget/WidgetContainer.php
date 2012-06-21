<?php

/**
 * This file is part of the Miny framework.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version accepted by the author in accordance with section
 * 14 of the GNU General Public License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   Miny/Widget
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 */

namespace Miny\Widget;

use \Miny\Template\Template;

class WidgetContainer {

    private $widgets = array();
    private $groups;
    private $templating;

    public function __construct(Template $templating) {
        $this->groups = array('no_group' => array());
        $this->templating = $templating;
    }

    public function addGroup($group) {
        $widgets = func_get_args();
        array_shift($widgets);
        
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

    public function addWidget($id, $widget) {
        if (!is_string($widget)
                && !is_callable($widget)
                && !$widget instanceof \Closure) {
            $message = sprintf('Invalid widget: %s (%s)', $id, gettype($widget));
            throw new \InvalidArgumentException($message);
        }

        if (!is_string($widget)) {
            $widget = func_get_args();
            array_shift($widget);
        }

        $this->widgets[$id] = array('widget' => $widget, 'params' => array());
    }

    public function setWidgetParams($id, array $params) {
        if (!$this->widgetExists($id)) {
            throw new \OutOfBoundsException('Widget not set: ' . $id);
        }
        return $this->widgets[$id]['params'] = $params;
    }

    public function removeWidget($id, $group = 'no_group') {
        unset($this->groups[$group][$id]);
        unset($this->widgets[$id]);
    }

    public function widgetExists($id) {
        return isset($this->widgets[$id]);
    }

    public function getWidgetParameters($id) {
        if (!$this->widgetExists($id)) {
            throw new \OutOfBoundsException('Widget not set: ' . $id);
        }
        return $this->widgets[$id]['params'];
    }

    public function getWidget($name, array $parameters = array()) {
        //TODO: factory kiiktatása

        if (!$this->widgetExists($name)) {
            if (!class_exists($name)) {
                throw new \InvalidArgumentException('Widget not found: ' . $name);
            }
            $widget = new $name;
        } elseif (is_string($this->widgets[$name]['widget'])) {
            if (!class_exists($this->widgets[$name]['widget'])) {
                throw new \InvalidArgumentException('Widget not found: ' . $this->widgets[$name]['widget']);
            }
            $widget = new $this->widgets[$name]['widget'];
        } else {
            $factory_params = $this->widgets[$name]['widget'];
            $callable = array_shift($factory_params);
            $widget = call_user_func_array($callable, $factory_params);
        }

        if (!$widget instanceof iWidget) {
            $pattern = 'Invalid widget: %s (class: %s)';
            $message = sprintf($pattern, $widget_name, get_class($widget));
            throw new \InvalidArgumentException($message);
        }
        $widget->setContainer($this);
        return $widget->begin($parameters);
    }

    public function renderWidget($id, array $parameters = array()) {
        if ($this->widgetExists($id)) {
            $parameters = $parameters + $this->getWidgetParameters($id);
        }
        return $this->getWidget($id)->end($parameters);
    }

    public function render(iWidget $widget, array $params = array()) {

        $this->templating->setScope('widget');

        if ($widget->run($params) === false) {
            return;
        }

        foreach ($widget->getAssigns() as $key => $value) {
            $this->templating->$key = $value;
        }

        if (is_null($widget->view)) {
            throw new \RuntimeException('Template not set.');
        }

        $response = $this->templating->render('widgets/' . $widget->view);

        $this->templating->leaveScope(true);
        return $response;
    }

}