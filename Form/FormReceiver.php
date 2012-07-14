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

use \Miny\Validator\Constraints\Choice;
use \Miny\Validator\Descriptor;
use \Miny\Validator\Validator;
use \Miny\Validator\iValidable;

class FormReceiver extends Validator
{
    private $form;

    public function __construct(FormDescriptor $form)
    {
        $this->form = $form;
    }

    protected function loadConstraints(iValidable $form)
    {
        $class = new Descriptor;
        $form->getValidationInfo($class);

        if ($this->form->getOption('csrf')) {
            $tokens = $form->getTokenStorage()->getTokens();
            $class->addGetterConstraint('getCSRFToken', new Choice($tokens));
        }

        return $class;
    }

    public function validateForm()
    {
        $result = parent::validate($this->form);
        if ($result === true) {
            return true;
        }

        $this->form->addErrors($result);
        return false;
    }

}