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

class Submit extends \FormEngine\Node
{

	protected function prepareAttributesJavascript ()
	{
		$attributes = Array(
			$this->formatAttributeJavascript('name', 'sName'),
			$this->formatAttributeJavascript('class', 'sClass'),
			$this->formatAttributeJavascript('label', 'sLabel'),
			$this->formatAttributeJavascript('icon', 'sIcon'),
			$this->formatDependencyJavascript()
		);
		return $attributes;
	}

	public function GetValue ()
	{
		return '';
	}

	public function Populate ($value)
	{
	}

}
