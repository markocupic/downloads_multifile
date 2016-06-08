<?php
/**
 * Created by PhpStorm.
 * User: Marko
 * Date: 05.06.2016
 * Time: 16:31
 */

namespace MCupic;


class MultifileDownloads
{
    /**
     * Path to zip-archive
     * @var string
     */
    public $archivePath = 'files/downloads_multifile';

    /**
     * file ids
     * @var array
     */
    protected $arrIDS = array();

    public function initializeSystem()
    {
        if (\Input::get('zipDownload') == 'true' && \Input::get('files') != '')
        {
            // Get file-IDS from $_GET
            $arrIds = explode(',', \Input::get('files'));

            // Optionally set zip folder
            $this->setArchivePath('files/downloads_multifile');
            // Send file to browser
            $this->sendZipToBrowser($arrIds);
        }
        if (\Input::get('ceDownloads') && \Input::get('loadLanguageData'))
        {
            $this->sendLanguageData();
        }
    }

    /**
     * Delete old/unused zip-archives
     */
    protected function deleteOldFolders()
    {
        foreach (scan(TL_ROOT . '/' . $this->archivePath) as $strFolder)
        {
            if (is_dir(TL_ROOT . '/' . $this->archivePath . '/' . $strFolder))
            {
                if (is_numeric($strFolder))
                {
                    if (time() - 3600 > intval($strFolder))
                    {
                        $objFolder = new \Folder($this->archivePath . '/' . $strFolder);
                        $objFolder->purge();
                        $objFolder->delete();
                    }
                }
            }
        }
    }


    /**
     * @param array $arrIDS
     * @throws \Exception
     */
    public function sendZipToBrowser(array $arrIDS)
    {

        // Delete old/unused zip-archives
        $this->deleteOldFolders();

        // Set $this->arrIDS
        $this->arrIDS = $arrIDS;

        $time = time();

        // Set zip-archive name/path
        $zipFilePath = $this->archivePath . '/' . $time . '/archive_' . $time . '.zip';

        // Create zip-archive folder
        new \Folder($this->archivePath . '/' . $time, true);

        // Initialize archive object
        $zip = new \ZipWriter($zipFilePath);

        // Add files to zip-archive
        foreach ($this->arrIDS as $id)
        {
            $objFile = \FilesModel::findByPk($id);
            if ($objFile !== null)
            {
                if (is_file(TL_ROOT . '/' . $objFile->path))
                {
                    \Files::getInstance()->copy($objFile->path, $this->archivePath . '/' . $time . '/' . $objFile->name);
                    $zip->addFile($this->archivePath . '/' . $time . '/' . $objFile->name);
                }
            }
        }


        // Zip archive will be created only after closing object
        $zip->close();

        // Send file to browser
        $objZipFile = new \File($zipFilePath);

        $objZipFile->sendToBrowser($zipFilePath);
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

    /**
     * xhr
     * Send language-data
     */
    public function sendLanguageData()
    {
        \Controller::loadLanguageFile('default');
        $json = array('done' => 'true');
        foreach ($GLOBALS['TL_LANG']['CTE']['ce_downloads'] as $k => $v)
        {
            $json[$k] = $v;
        }

        echo json_encode($json);
        exit;
    }
}