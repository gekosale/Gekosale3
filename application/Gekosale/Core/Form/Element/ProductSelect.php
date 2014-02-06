<?php

/**
 * Gekosale, Open Source E-Commerce Solution 
 * 
 * For the full copyright and license information, 
 * please view the LICENSE file that was distributed with this source code. 
 * 
 * @category    Gekosale 
 * @package     Gekosale\Core\Form
 * @subpackage  Gekosale\Core\Form\Element
 * @author      Adam Piotrowski <adam@gekosale.com>
 * @copyright   Copyright (c) 2008-2014 Gekosale sp. z o.o. (http://www.gekosale.com)
 */
namespace Gekosale\Core\Form\Element;

class ProductSelect extends Select
{

    public $datagrid;

    protected $_jsFunction;

    public function __construct ($attributes)
    {
        parent::__construct($attributes);
        $this->_jsFunction = 'LoadProducts_' . $this->_id;
        $this->_attributes['jsfunction'] = 'xajax_' . $this->_jsFunction;
        App::getRegistry()->xajax->registerFunction(array(
            $this->_jsFunction,
            $this,
            'loadProducts_' . $this->_id
        ));
        $this->_attributes['load_category_children'] = App::getRegistry()->xajaxInterface->registerFunction(array(
            'LoadCategoryChildren_' . $this->_id,
            $this,
            'loadCategoryChildren'
        ));
        if (isset($this->_attributes['exclude_from'])) {
            $this->_attributes['exclude_from_field'] = $this->_attributes['exclude_from']->GetName();
        }
        if (! isset($this->_attributes['exclude'])) {
            $this->_attributes['exclude'] = Array(
                0
            );
        }
        $this->_attributes['datagrid_filter'] = $this->getDatagridFilterData();
    }

    public function __call ($function, $arguments)
    {
        if ($function == 'loadProducts_' . $this->_id) {
            return call_user_func_array(Array(
                $this,
                'loadProducts'
            ), $arguments);
        }
    }

    protected function _PrepareAttributes_JS ()
    {
        $attributes = Array(
            $this->_FormatAttribute_JS('name', 'sName'),
            $this->_FormatAttribute_JS('label', 'sLabel'),
            $this->_FormatAttribute_JS('comment', 'sComment'),
            $this->_FormatAttribute_JS('error', 'sError'),
            $this->_FormatAttribute_JS('exclude_from_field', 'sExcludeFrom'),
            $this->_FormatAttribute_JS('jsfunction', 'fLoadProducts', FE::TYPE_FUNCTION),
            $this->_FormatAttribute_JS('advanced_editor', 'bAdvancedEditor', FE::TYPE_BOOLEAN),
            $this->_FormatAttribute_JS('datagrid_filter', 'oFilterData', FE::TYPE_OBJECT),
            $this->_FormatAttribute_JS('load_category_children', 'fLoadCategoryChildren', FE::TYPE_FUNCTION),
            $this->_FormatRepeatable_JS(),
            $this->_FormatRules_JS(),
            $this->_FormatDependency_JS(),
            $this->_FormatDefaults_JS()
        );
        return $attributes;
    }

    public function loadCategoryChildren ($request)
    {
        return Array(
            'aoItems' => $this->getCategories($request['parentId'])
        );
    }

    protected function getCategories ($parent = 0)
    {
        $categories = App::getModel('category')->getChildCategories($parent);
        usort($categories, Array(
            $this,
            'sortCategories'
        ));
        return $categories;
    }

    protected function sortCategories ($a, $b)
    {
        return $a['weight'] - $b['weight'];
    }

    public function loadProducts ($request, $processFunction)
    {
        if (isset($request['dynamic_exclude']) and is_array($request['dynamic_exclude'])) {
            $this->_attributes['exclude'] = array_merge($this->_attributes['exclude'], $request['dynamic_exclude']);
        }
        else {
            $this->_attributes['exclude'] = Array(
                0
            );
        }
        $this->getDatagrid()->setAdditionalWhere('
			P.idproduct NOT IN (' . implode(',', $this->_attributes['exclude']) . ')
		');
        
        return $this->getDatagrid()->getData($request, $processFunction);
    }

    public function getDatagrid ()
    {
        if (($this->datagrid == NULL)) {
            $this->datagrid = App::getModel('datagrid/datagrid');
            $this->initDatagrid($this->datagrid);
        }
        return $this->datagrid;
    }

    public function getDatagridFilterData ()
    {
        return $this->getDatagrid()->getFilterData();
    }

    public function processVariants ($productId)
    {
        $rawVariants = App::getModel('product/product')->getAttributeCombinationsForProduct($productId);
        $variants = Array();
        $variants[] = Array(
            'id' => '',
            'caption' => $this->trans('TXT_ANY_VARIANT')
        );
        foreach ($rawVariants as $variant) {
            $caption = Array();
            foreach ($variant['attributes'] as $attribute) {
                $caption[] = $attribute['name'];
            }
            $variants[] = Array(
                'id' => $variant['id'],
                'caption' => implode(', ', $caption)
            );
        }
        return json_encode($variants);
    }

    protected function initDatagrid ($datagrid)
    {
        $datagrid->setTableData('product', Array(
            'idproduct' => Array(
                'source' => 'P.idproduct'
            ),
            'name' => Array(
                'source' => 'PT.name',
                'prepareForAutosuggest' => true
            ),
            'categoryname' => Array(
                'source' => 'CT.name'
            ),
            'categoryid' => Array(
                'source' => 'PC.categoryid',
                'prepareForTree' => true,
                'first_level' => $this->getCategories()
            ),
            'ancestorcategoryid' => Array(
                'source' => 'CP.ancestorcategoryid'
            ),
            'categoriesname' => Array(
                'source' => 'GROUP_CONCAT(DISTINCT SUBSTRING(CONCAT(\' \', CT.name), 1))',
                'filter' => 'having'
            ),
            'sellprice' => Array(
                'source' => 'P.sellprice'
            ),
            'sellprice_gross' => Array(
                'source' => 'ROUND(P.sellprice * (1 + V.value / 100), 2)'
            ),
            'barcode' => Array(
                'source' => 'P.barcode',
                'prepareForAutosuggest' => true
            ),
            'ean' => Array(
                'source' => 'P.ean'
            ),
            'buyprice' => Array(
                'source' => 'P.buyprice'
            ),
            'buyprice_gross' => Array(
                'source' => 'ROUND(P.buyprice * (1 + V.value / 100), 2)'
            ),
            'producer' => Array(
                'source' => 'PRT.name',
                'prepareForSelect' => true
            ),
            'vat' => Array(
                'source' => 'CONCAT(V.value, \'%\')',
                'prepareForSelect' => true
            ),
            'stock' => Array(
                'source' => 'stock'
            ),
            'variant__options' => Array(
                'source' => 'P.idproduct',
                'processFunction' => ((isset($this->_attributes['advanced_editor']) && $this->_attributes['advanced_editor']) ? Array(
                    $this,
                    'processVariants'
                ) : false)
            ),
            'thumb' => Array(
                'source' => 'PP.photoid',
                'processFunction' => Array(
                    $this,
                    'getThumbPathForId'
                )
            )
        ));
        $datagrid->setFrom('
			product P
			LEFT JOIN producttranslation PT ON P.idproduct = PT.productid AND PT.languageid = :languageid
			LEFT JOIN productcategory PC ON PC.productid = P.idproduct
			LEFT JOIN productphoto PP ON PP.productid = P.idproduct AND PP.mainphoto = 1
			LEFT JOIN category C ON C.idcategory = PC.categoryid
			LEFT JOIN categorypath CP ON C.idcategory = CP.categoryid
			LEFT JOIN viewcategory VC ON PC.categoryid = VC.categoryid
			LEFT JOIN categorytranslation CT ON C.idcategory = CT.categoryid AND CT.languageid = :languageid
			LEFT JOIN producer R ON P.producerid = R.idproducer
			LEFT JOIN producertranslation PRT ON P.producerid = PRT.producerid AND PRT.languageid = :languageid
			LEFT JOIN `vat` V ON P.vatid = V.idvat
		');
        
        $datagrid->setGroupBy('
			P.idproduct
		');
        
        if (Helper::getViewId() > 0) {
            $datagrid->setAdditionalWhere('
				IF(PC.categoryid IS NOT NULL, VC.viewid IN (' . Helper::getViewIdsAsString() . '), 1)
			');
        }
        
        if (isset($this->_attributes['additional_rows'])) {
            $datagrid->setAdditionalRows($this->_attributes['additional_rows']);
        }
    }

    public function getThumbPathForId ($id)
    {
        if ($id > 1) {
            try {
                $image = App::getModel('gallery')->getSmallImageById($id);
            }
            catch (Exception $e) {
                $image = Array(
                    'path' => ''
                );
            }
            return $image['path'];
        }
        else {
            return '';
        }
    }
}