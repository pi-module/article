<?php
/**
 * Article module visit class
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

namespace Module\Article\Model;

use Pi;
use Pi\Application\Model\Model;
use Zend\Db\Sql\Expression;

class Visit extends Model
{
    /**
     * Add a row.
     *
     * @param int  $id  Article ID
     * @return array
     */
    public function addRow($id)
    {
        $user   = Pi::service('user')->getUser();
        $server = Pi::engine()->application()->getRequest()->getServer();
        $data   = array(
            'article'  => $id,
            'time'     => time(),
            'ip'       => $server['REMOTE_ADDR'],
            'uid'      => $user->account->id ?: 0,
        );
        $row    = $this->createRow($data);
        $result = $row->save();

        return $result;
    }
}
