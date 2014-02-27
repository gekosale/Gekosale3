<?php
namespace FormEngine\Elements;
/**
 * Gekosale, Open Source E-Commerce Solution
 * http://www.gekosale.pl
 *
 * Copyright (c) 2009-2011 Gekosale
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms
 * of the GNU General Public License Version 3, 29 June 2007 as published by the
 * Free Software
 * Foundation (http://opensource.org/licenses/gpl-3.0.html).
 * If you did not receive a copy of the license and are unable to obtain it
 * through the
 * world-wide-web, please send an email to license@verison.pl so we can send you
 * a copy immediately.
 */

class StaticText extends \FormEngine\Node
{

	public function __construct ($attributes)
	{
		parent::__construct($attributes);
		$this->_attributes['name'] = '';
	}

	protected function prepareAttributesJs ()
	{
		$attributes = Array(
			$this->formatAttributeJs('text', 'sText'),
			$this->formatAttributeJs('class', 'sClass'),
			$this->formatDependencyJs()
		);
		return $attributes;
	}

	public function Render_Static ()
	{
	}

	public function Populate ($value)
	{
	}

}