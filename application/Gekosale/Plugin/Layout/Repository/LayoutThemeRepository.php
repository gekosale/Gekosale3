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
namespace Gekosale\Plugin\Layout\Repository;

use Gekosale\Core\Repository;
use Gekosale\Core\Model\LayoutTheme;
use Gekosale\Core\Model\LayoutThemeTranslation;

/**
 * Class LayoutThemeRepository
 *
 * @package Gekosale\Plugin\LayoutTheme\Repository
 * @author  Adam Piotrowski <adam@gekosale.com>
 */
class LayoutThemeRepository extends Repository
{

    /**
     * Returns all tax rates
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function all()
    {
        return LayoutTheme::all();
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
        return LayoutTheme::with('translation')->findOrFail($id);
    }

    /**
     * Deletes layout_theme record by ID
     *
     * @param int $id layout_theme ID to delete
     */
    public function delete($id)
    {
        $this->transaction(function () use ($id) {
            return LayoutTheme::destroy($id);
        });
    }

    /**
     * Saves layout_theme
     *
     * @param      $Data
     * @param null $id
     */
    public function save($Data, $id = null)
    {
        $this->transaction(function () use ($Data, $id) {

            $layout_theme = LayoutTheme::firstOrNew([
                'id' => $id
            ]);

            $layout_theme->discount = $Data['discount'];
            $layout_theme->save();

            foreach ($this->getLanguageIds() as $language) {

                $translation = LayoutThemeTranslation::firstOrNew([
                    'layout_theme_id' => $layout_theme->id,
                    'language_id'     => $language
                ]);

                $translation->setTranslationData($Data, $language);
                $translation->save();
            }

        });
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
        $layout_themeData = $this->find($id);
        $populateData     = [];
        $accessor         = $this->getPropertyAccessor();
        $languageData     = $layout_themeData->getTranslationData();

        $accessor->setValue($populateData, '[required_data]', [
            'discount'      => $layout_themeData->discount,
            'language_data' => $languageData,
        ]);

        return $populateData;
    }

    public function getAllLayoutThemeToSelect()
    {
        return $this->all()->toSelect('id', 'name');
    }
}