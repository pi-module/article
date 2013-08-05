<?php
/**
 * Article module account element
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
 */

namespace Module\Article\Form\Element;

use Pi;
use Zend\Form\Element\Select;

/**
 * Class for creating select of accounts belong to article module
 */
class Account extends Select
{
    /**
     * Reading account from database
     * 
     * @return array 
     */
    public function getValueOptions()
    {
        if (empty($this->valueOptions)) {
            // Getting children role of article module
            $rowRole = Pi::model('acl_role')->getChildren('member');
            $roles   = array(0);
            foreach ($rowRole as $row) {
                $roles[$row->id] = $row->name; 
            }
            
            // Getting account ID
            $rowset = Pi::model('user_role')->select(array('role' => $roles));
            $ids    = array();
            foreach ($rowset as $row) {
                $ids[$row->user] = $row->user;
            }

            // Getting active account
            $where  = array(
                'active'  => 1,
            );
            $model  = Pi::model('user');
            $select = $model->select()->where($where)
                                      ->columns(array('id', 'name'))
                                      ->order(array('name ASC'));
            $rowset = $model->selectWith($select);
            $account = array(0 => __('Null'));
            foreach ($rowset as $row) {
                $account[$row->id] = $row->name;
            }
            
            $this->valueOptions = $account;
        }

        return $this->valueOptions;
    }
}
