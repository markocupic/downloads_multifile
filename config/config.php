<?php
/**
 * Created by PhpStorm.
 * User: Marko
 * Date: 23.05.2016
 * Time: 20:48
 */

if (TL_MODE == 'FE')
{
    if (\Input::get('zipDownload') == 'true' && \Input::get('files') != '')
    {
        // Get file-IDS from $_GET
        $arrIds = explode(',', \Input::get('files'));
        // Initialize download-object
        $objZip = new \MCupic\MultifileDownloadsHelper();
        // Optionally set zip folder
        $objZip->setArchivePath('files/downloads_multifile');
        // Send file to browser
        $objZip->sendZipToBrowser($arrIds);
    }
}