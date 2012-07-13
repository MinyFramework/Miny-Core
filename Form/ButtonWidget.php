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
use \Miny\Session\Session;
use \Miny\Form\Elements\Button;
use \Miny\Form\Elements\Image;

class ButtonWidget extends Widget
{
    private $session;

    public function setSession(Session $session)
    {
        $this->session = $session;
    }

    public function run(array $params = array())
    {
        $required_keys = array('url', 'method');
        $missing = array_diff($required_keys, array_keys($params));
        if (!empty($missing)) {
            $string = implode('", "', $missing);
            $message = sprintf('Parameters "%s" are missing.', $string);
            throw new \InvalidArgumentException($message);
        }

        if (isset($params['form'])) {
            $form_params = $params['form'];
            unset($params['form']);
        } else {
            $form_params = array();
        }
        $form_params['action'] = $params['url'];
        $form_params['method'] = $params['method'];
        unset($params['url'], $params['method']);
        $descriptor = new FormDescriptor;

        if (is_null($this->session)) {
            $descriptor->setOption('csrf', false);
        } else {
            $tokens = $this->session->getFlash('tokens', array());
            $tokens[] = $login->getCSRFToken();
            $this->session->setFlash('tokens', $tokens);
        }

        if (isset($params['src'])) {
            $descriptor->addField(new Image('button', $params['src'], $params));
        } else {
            $value = isset($params['value']) ? $params['value'] : NULL;
            $descriptor->addField(new Button('button', $value, $params));
        }
        $form = new FormBuilder($descriptor);
        echo $form->begin($form_params);
        echo $form->render('button');
        echo $form->end();
        return false;
    }

}