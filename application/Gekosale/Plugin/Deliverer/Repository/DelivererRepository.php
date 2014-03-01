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
namespace Gekosale\Plugin\Deliverer\Repository;

use Gekosale\Core\Repository;
use Gekosale\Core\Model\Deliverer;
use Gekosale\Core\Model\DelivererTranslation;

/**
 * Class DelivererRepository
 *
 * @package Gekosale\Plugin\Deliverer\Repository
 * @author  Adam Piotrowski <adam@gekosale.com>
 */
class DelivererRepository extends Repository
{

    /**
     * Returns all tax rates
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function all()
    {
        return Deliverer::all();
    }

    /**
     * Returns a single tax rate
     *
     * @param $id
     *
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|static
     */
    public function find($id)
    {
        return Deliverer::with('translation')->findOrFail($id);
    }

    /**
     * Deletes tax rate by ID
     *
     * @param $id
     */
    public function delete($id)
    {
        return Deliverer::destroy($id);
    }

    /**
     * Saves deliverer
     *
     * @param      $Data
     * @param null $id
     */
    public function save($Data, $id = null)
    {
        $deliverer = Deliverer::firstOrCreate([
            'id' => $id
        ]);

        foreach ($Data['name'] as $languageId => $name) {

            $translation = DelivererTranslation::firstOrCreate([
                'deliverer_id' => $deliverer->id,
                'language_id'  => $languageId
            ]);

            $translation->name = $name;

            $translation->save();
        }

        $deliverer->save();
    }

    /**
     * Returns array containing values needed to populate the form
     *
     * @param $id
     *
     * @return array
     */
    public function getPopulateData($id)
    {
        $delivererData = $this->find($id);

        return [
            'required_data' => [
                'language_data' => $delivererData->getLanguageData()
            ]
        ];
    }
}