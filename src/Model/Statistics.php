<?php
/**
 * Article module statistics class
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

class Statistics extends Model
{
    /**
     * Increase visit count of a article.
     *
     * @param int  $id  Article ID
     * @return array
     */
    public function increaseVisits($id)
    {
        $row = $this->find($id, 'article');
        if (!$row->id) {
            $data = array(
                'article'  => $id,
                'visits'   => 1,
            );
            $row    = $this->createRow($data);
            $result = $row->save();
        } else {
            $row->visits++;
            $result = $row->save();
        }

        return $result;
    }
}
