<?php
/**
 * Pi Engine (http://pialog.org)
 *
 * @link         http://code.pialog.org for the Pi Engine source repository
 * @copyright    Copyright (c) Pi Engine http://pialog.org
 * @license      http://pialog.org/license.txt New BSD License
 */

namespace Module\Article\Model;

use Pi;
use Pi\Application\Model\Model;

/**
 * Statistics model class
 * 
 * @author Zongshu Lin <lin40553024@163.com>
 */
class Statistics extends Model
{
    /**
     * Get avaliable columns
     * 
     * @return array 
     */
    public static function getAvailableColumns()
    {
        $columns = array('visits');
        
        return $columns;
    }
    
    /**
     * Increase visit count of a article.
     *
     * @param int  $id  Article ID
     * @return array
     */
    public function increaseVisits($id)
    {
        $row = $this->find($id, 'article');
        if (empty($row)) {
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
