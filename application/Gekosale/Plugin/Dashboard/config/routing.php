<?php
/*
 * Gekosale Open-Source E-Commerce Platform
 *
 * This file is part of the Gekosale package.
 *
 * (c) Adam Piotrowski <adam@gekosale.com>
 *
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 */
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

$collection = new RouteCollection();

$controller = 'Gekosale\Plugin\Dashboard\Controller\Admin\DashboardController';

$collection->add('admin.dashboard.index', new Route('/index', array(
    '_controller' => $controller,
    '_mode'       => 'admin',
    '_action'     => 'indexAction'
)));

$collection->addPrefix('/admin/dashboard');

return $collection;
