<?php
/**
 * Gekosale, Open Source E-Commerce Solution
 * http://www.gekosale.pl
 *
 * Copyright (c) 2009-2011 Gekosale
 *
 * This program is free software; you can redistribute it and/or modify it under the terms
 * of the GNU General Public License Version 3, 29 June 2007 as published by the Free Software
 * Foundation (http://opensource.org/licenses/gpl-3.0.html).
 * If you did not receive a copy of the license and are unable to obtain it through the
 * world-wide-web, please send an email to license@verison.pl so we can send you a copy immediately.
 */

namespace FormEngine\Elements;

use Gekosale\App as App;
use Gekosale\Translation as Translation;
use FormEngine\FE as FE;

class LocalFile extends File
{

    public function __construct($attributes)
    {
        parent::__construct($attributes);
        if (!isset($this->_attributes['traversable'])) {
            $this->_attributes['traversable'] = true;
        }
        if (!isset($this->_attributes['file_types'])) {
            $this->_attributes['file_types'] = Array(
                'jpg',
                'png',
                'gif',
                'swf'
            );
        }
        $this->_attributes['file_types_description'] = Translation::get('TXT_FILE_TYPES_IMAGE');
        $this->_attributes['upload_url']
                                                     = App::getURLAdressWithAdminPane() . 'files/add/' . base64_encode($this->_attributes['file_source']);
        $this->_attributes['load_handler']           = 'xajax_LoadFiles_' . $this->_id;
        App::getRegistry()->xajaxInterface->registerFunction(array(
            'LoadFiles_' . $this->_id,
            $this,
            'LoadFiles'
        ));
        $this->_attributes['delete_handler'] = 'xajax_DeleteFile_' . $this->_id;
        App::getRegistry()->xajaxInterface->registerFunction(array(
            'DeleteFile_' . $this->_id,
            $this,
            'DeleteFile'
        ));
        $this->_attributes['type_icons'] = Array(
            'cdup'      => DESIGNPATH . '_images_panel/icons/filetypes/cdup.png',
            'unknown'   => DESIGNPATH . '_images_panel/icons/filetypes/unknown.png',
            'directory' => DESIGNPATH . '_images_panel/icons/filetypes/directory.png',
            'gif'       => DESIGNPATH . '_images_panel/icons/filetypes/image.png',
            'png'       => DESIGNPATH . '_images_panel/icons/filetypes/image.png',
            'jpg'       => DESIGNPATH . '_images_panel/icons/filetypes/image.png',
            'bmp'       => DESIGNPATH . '_images_panel/icons/filetypes/image.png',
            'txt'       => DESIGNPATH . '_images_panel/icons/filetypes/text.png',
            'doc'       => DESIGNPATH . '_images_panel/icons/filetypes/text.png',
            'rtf'       => DESIGNPATH . '_images_panel/icons/filetypes/text.png',
            'odt'       => DESIGNPATH . '_images_panel/icons/filetypes/text.png',
            'htm'       => DESIGNPATH . '_images_panel/icons/filetypes/document.png',
            'html'      => DESIGNPATH . '_images_panel/icons/filetypes/document.png',
            'php'       => DESIGNPATH . '_images_panel/icons/filetypes/document.png'
        );
    }

    protected function prepareAttributesJavascript()
    {
        $attributes = Array(
            $this->formatAttributeJavascript('name', 'sName'),
            $this->formatAttributeJavascript('label', 'sLabel'),
            $this->formatAttributeJavascript('comment', 'sComment'),
            $this->formatAttributeJavascript('error', 'sError'),
            $this->formatAttributeJavascript('selector', 'sSelector'),
            $this->formatAttributeJavascript('file_source', 'sFilePath'),
            $this->formatAttributeJavascript('upload_url', 'sUploadUrl'),
            $this->formatAttributeJavascript('session_name', 'sSessionName'),
            $this->formatAttributeJavascript('session_id', 'sSessionId'),
            $this->formatAttributeJavascript('file_types', 'asFileTypes'),
            $this->formatAttributeJavascript('type_icons', 'oTypeIcons', FE::TYPE_OBJECT),
            $this->formatAttributeJavascript('file_types_description', 'sFileTypesDescription'),
            $this->formatAttributeJavascript('delete_handler', 'fDeleteFile', FE::TYPE_FUNCTION),
            $this->formatAttributeJavascript('load_handler', 'fLoadFiles', FE::TYPE_FUNCTION),
            $this->formatRepeatableJavascript(),
            $this->formatRulesJavascript(),
            $this->formatDependencyJavascript(),
            $this->formatDefaultsJavascript()
        );
        return $attributes;
    }

    public function DeleteFile($request)
    {
        if (!isset($request['file'])) {
            throw new Exception('No file specified.');
        }
        if (substr($request['file'], 0, strlen($this->_attributes['file_source'])) != $this->_attributes['file_source']) {
            throw new Exception('The requested path "' . $request['file'] . '" is outside of permitted sandbox.');
        }
        if (!unlink($request['file'])) {
            throw new Exception('Deletion of file "' . $request['file'] . '" unsuccessful.');
        }
        return Array();
    }

    public function LoadFiles($request)
    {
        $inRoot = false;
        if (substr($request['path'], 0, strlen($this->_attributes['file_source'])) != $this->_attributes['file_source']) {
            $request['path'] = $this->_attributes['file_source'];
        }
        if ($request['path'] == $this->_attributes['file_source']) {
            $inRoot = true;
        }
        $path  = ROOTPATH . $request['path'];
        $files = Array();
        $dirs  = Array();
        if (($dir = opendir($path)) === false) {
            throw new Exception('Directory "' + $path + '" cannot be listed.');
        }
        while (($file = readdir($dir)) !== false) {
            if ($file == '.') {
                continue;
            }
            if ($inRoot && ($file == '..')) {
                continue;
            }
            $filepath = $path . $file;
            if ($file != '.svn') {
                if (is_dir($filepath) && $this->_attributes['traversable']) {
                    $dirs[] = Array(
                        'dir'   => true,
                        'name'  => $file,
                        'path'  => $request['path'] . $file,
                        'size'  => '',
                        'owner' => '' . fileowner($filepath),
                        'mtime' => date('Y-m-d H:i:s', filemtime($filepath))
                    );
                } else {

                    if (in_array(pathinfo($request['path'] . $file, PATHINFO_EXTENSION), $this->_attributes['file_types'])) {
                        $files[] = Array(
                            'dir'   => false,
                            'name'  => $file,
                            'path'  => $request['path'] . $file,
                            'size'  => '' . filesize($filepath),
                            'owner' => '' . fileowner($filepath),
                            'mtime' => date('Y-m-d H:i:s', filemtime($filepath))
                        );
                    }
                }
            }
        }
        closedir($dir);
        return Array(
            'files' => array_merge($dirs, $files),
            'cwd'   => $request['path']
        );
    }

}
