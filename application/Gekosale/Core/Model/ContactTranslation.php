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
namespace Gekosale\Core\Model;

use Gekosale\Core\Model;

/**
 * Class ContactTranslation
 *
 * @package Gekosale\Core\Model
 * @author  Adam Piotrowski <adam@gekosale.com>
 */
class ContactTranslation extends Model
{

    protected $table = 'contact_translation';

    public $timestamps = true;

    protected $softDelete = false;

    protected $fillable = ['contact_id', 'language_id'];

    protected $translatable
        = [
            'name',
            'email',
            'phone',
            'street',
            'streetno',
            'flatno',
            'province',
            'city',
            'country'
        ];

    public function language()
    {
        return $this->belongsTo('Gekosale\Core\Model\Language');
    }
}