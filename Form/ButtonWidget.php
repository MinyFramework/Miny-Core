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
 * @package   Miny/Form
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 */

namespace Miny\Form;

use \Miny\Widget\Widget;
use \Miny\Form\Elements\Button;

class ButtonWidget extends Widget
{
    public function run($action, $method = 'POST', array $params = array())
    {
        $form_params = array();
        if (isset($params['form'])) {
            $form_params = array_merge($form, $params['form']);
            unset($params['form']);
        }
        $form_params['action'] = $action;
        $form_params['method'] = $method;
        $form = new FormBuilder;
        if (isset($params['src'])) {
            $form->addField(new Image($params));
        } else {
            $form->addField(new Button($params));
        }
        echo $form->generate($form_params);
        return false;
    }

}