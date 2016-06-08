<?php
/**
 * Created by PhpStorm.
 * User: Marko
 * Date: 23.05.2016
 * Time: 20:48
 */

if (TL_MODE == 'FE')
{

    $GLOBALS['TL_HOOKS']['initializeSystem'][] = array('\MCupic\MultifileDownloads', 'initializeSystem');

    $GLOBALS['TL_CSS'][] = 'system/modules/downloads_multifile/assets/ce_downloads_multifile.css';
    $GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/downloads_multifile/assets/ce_downloads_multifile.js';
}