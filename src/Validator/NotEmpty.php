<?php
/**
 * Article module empty value validator
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

class NotEmpty extends AbstractValidator
{
    const IS_EMPTY        = 'isEmpty';

    /**
     * @var array
     */
    protected $messageTemplates = array(
        self::IS_EMPTY     => 'The value is required!',
    );

    /**
     * Empty value validate
     *
     * @param  mixed  $value
     * @param  array  $context
     * @return boolean
     */
    public function isValid($value)
    {
        $this->setValue($value);

        if (empty($value)) {
            $this->error(self::IS_EMPTY);
            return false;
        }

        return true;
    }
}
