<?php
/**
 * Article module statistics controller
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

namespace Module\Article\Controller\Admin;

use Pi\Mvc\Controller\ActionController;
use Pi;
use Module\Article\Service;

class StatisticsController extends ActionController
{
    public function indexAction()
    {
        $topVisitsEver = Service::getTotalVisits(10);
        $topVisits7    = Service::getVisitsRecently(7, 10);
        $topVisits30   = Service::getVisitsRecently(30, 10);

        $totalEver = Service::getTotalRecently();
        $total7    = Service::getTotalRecently(7);
        $total30   = Service::getTotalRecently(30);

        $totalEverByCategory = Service::getTotalRecentlyByCategory();
        $total7ByCategory    = Service::getTotalRecentlyByCategory(7);
        $total30ByCategory   = Service::getTotalRecentlyByCategory(30);

        $topSubmittersEver = Service::getSubmittersRecently(null, 10);
        $topSubmitters7    = Service::getSubmittersRecently(7, 10);
        $topSubmitters30   = Service::getSubmittersRecently(30, 10);

        if ($this->config['enable-tag']) {
            $topTags = Pi::service('api')->tag->top($this->getModule(), null, 10);
            $this->view()->assign('topTags', $topTags);
        }

        $this->view()->assign(array(
            'title'               => __('Statistic'),

            'topVisitsEver'       => $topVisitsEver,
            'topVisits7'          => $topVisits7,
            'topVisits30'         => $topVisits30,

            'totalEver'           => $totalEver,
            'total7'              => $total7,
            'total30'             => $total30,

            'totalEverByCategory' => $totalEverByCategory,
            'total7ByCategory'    => $total7ByCategory,
            'total30ByCategory'   => $total30ByCategory,

            'topSubmittersEver'   => $topSubmittersEver,
            'topSubmitters7'      => $topSubmitters7,
            'topSubmitters30'     => $topSubmitters30,
        ));
    }
}
