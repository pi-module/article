<?php
/**
 * Article module repeat name validator
 *
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @copyright       Copyright (c) Pi Engine http://www.xoopsengine.org
 * @license         http://www.xoopsengine.org/license New BSD License
 * @author          Zongshu Lin <zongshu@eefocus.com>
 * @since           1.0
 * @package         Module\Article
 * @subpackage      Validator
 */

namespace Module\Article\Validator;

use Pi;
use Zend\Validator\AbstractValidator;

class RepeatName extends AbstractValidator
{
    const NAME_EXISTS        = 'nameExists';

    /**
     * @var array
     */
    protected $messageTemplates = array(
        self::NAME_EXISTS     => 'The name is already exists in database!',
    );

    /**
     * Repeat validate
     *
     * @param  mixed  $value
     * @param  array  $context
     * @return boolean
     */
    public function isValid($value)
    {
        $this->setValue($value);

        $options = $this->getOptions();
        $module  = Pi::service('module')->current();
        $row     = Pi::model($options['table'], $module)->find($value, 'name');
        if ($row) {
            $this->error(self::NAME_EXISTS);
            return false;
        }

        return true;
    }
}
