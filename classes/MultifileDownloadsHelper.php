<?php
/**
 * Created by PhpStorm.
 * User: Marko
 * Date: 05.06.2016
 * Time: 16:31
 */

namespace MCupic;


class MultifileDownloadsHelper
{
    public $archivePath = 'files/downloads_multifile';

    protected $arrIDS = array();

    /**
     * @param array $arrIDS
     * @throws \Exception
     */
    public function sendZipToBrowser(array $arrIDS)
    {
        $this->arrIDS = $arrIDS;

        $time = time();
        $zipFile = 'archive_' . time() . '.zip';

        // Initialize archive object
        $zip = new \ZipWriter($zipFile);
        $objFolder = new \Folder($this->archivePath . '/' . $time, true);


        foreach ( $this->arrIDS  as $id)
        {
            $objFile = \FilesModel::findByPk($id);
            if ($objFile !== null)
            {
                if (is_file(TL_ROOT . '/' . $objFile->path))
                {
                    $strDestination = $objFolder->path . '/' . $objFile->name;
                    \Files::getInstance()->copy($objFile->path, $strDestination);
                    $zip->addFile($strDestination);
                }
            }
        }


        // Zip archive will be created only after closing object
        $zip->close();

        $objZipFile = new \File($zipFile);
        $objFolder->delete();

        // Delete parent folder
        $objFolder = new \Folder($this->archivePath);
        if($objFolder->isEmpty())
        {
            $objFolder->delete();
        }

        // Send file to browser
        $objZipFile->sendToBrowser($zipFile);
        exit();
    }

    /**
     * @param string $strPath
     */
    public function setArchivePath($strPath = '')
    {
        if ($strPath != '')
        {
            if (is_dir(TL_ROOT . '/' . $strPath))
            {
                $this->archivePath = $strPath;
            }
        }
    }
}