<?php

/**
 * Gekosale, Open Source E-Commerce Solution
 * http://www.gekosale.pl
 *
 * Copyright (c) 2008-2012 Gekosale sp. z o.o.. Zabronione jest usuwanie informacji o licencji i autorach.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 *
 * $Revision: 438 $
 * $Author: gekosale $
 * $Date: 2011-08-27 11:29:36 +0200 (So, 27 sie 2011) $
 * $Id: productsearch.php 438 2011-08-27 09:29:36Z gekosale $
 */
namespace Gekosale\Component\Productsearch\Controller\Frontend;
use Gekosale\Core\Component\Controller\Frontend;

class Search extends Frontend
{

    public function __construct ($registry, $designPath = NULL)
    {
        parent::__construct($registry, $designPath);
        
        /**
		 * Error 404
		 *
		 * Jesli przejdziemy na adres.pl/wyszukiwarka
		 * jesli przejdziemy na adres inny niz adres.pl/wyszukiwarka/index/..., adres.pl/wyszukiwarka/noresults/...
		 * czyli w przypadku kiedy metoda nie istnieje
		 */
        $len = strlen(Seo::getSeo('productsearch'));
        
        if (substr($this->registry->router->getUri(), 0 - $len) === Seo::getSeo('productsearch') || ! in_array($this->registry->router->getAction(), array(
            'index',
            'noresults'
        ))){
            header('HTTP/1.1 404 Not Found');
            $this->registry->template->display($this->loadTemplate('error/index/layout.tpl'));
            exit();
        }
    }

    public function index ()
    {
        $this->Render('Productsearchlist');
    }

    public function noresults ()
    {
        $metadata = $this->getMetadata();
        $this->registry->template->assign('metadata', $this->getMetadata());
        $this->registry->template->assign('additionalmeta', isset($metadata['additionalmeta']) ? $metadata['additionalmeta'] : '');
        $this->registry->xajax->processRequest();
        $this->registry->template->assign('xajax', $this->registry->xajax->getJavascript());
        $this->searchPhrase = $this->getParam();
        
        $this->registry->template->assign('phrase', $this->searchPhrase);
        $this->registry->template->assign('dataset', App::getModel('recommendations')->getPromotions(5));
        $this->registry->template->assign('view', 0);
        $this->registry->template->display($this->loadTemplate('layout.tpl'));
    }

    public function ajaxIndex ()
    {
        $param = str_replace('_', '', App::getModel('formprotection')->cropDangerousCode($this->getParam()));
        
        if (strlen($param) > 2){
            $dataset = App::getModel('searchresults')->getDataset();
            $dataset->setPagination(5);
            $dataset->setCurrentPage(1);
            $dataset->setOrderBy('name', 'name');
            $dataset->setOrderDir('asc', 'asc');
            $dataset->setSQLParams(Array(
                'name' => '%' . str_replace(' ', '%', $param) . '%'
            ));
            $products = App::getModel('searchresults')->getProductDataset();
            $this->registry->template->assign('items', $products['rows']);
            $this->registry->template->assign('phrase', base64_encode($param));
            $result = $this->registry->template->fetch($this->loadTemplate('items.tpl'));
            App::getModel('searchresults')->addPhrase($param);
            echo $result;
            die();
        }
        else{
            echo '&nbsp;';
            die();
        }
    }
}