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
     * Content element model
     * @var
     */
    protected $objElement;

    /**
     * Files that are selected in the content element
     * @var array
     */
    protected $arrValidFileIDS = array();

    /**
     * Files to download
     * @var array
     */
    protected $arrFileIDS = array();


    /**
     * Hook Method
     * @param \ContentModel $objElement
     * @param $strBuffer
     * @return mixed
     */
    public function getContentElement(\ContentModel $objElement, $strBuffer)
    {

        if (\Input::get('zipDownload') == 'true' && \Input::get('files') != '' && $objElement->id == \Input::get('elId'))
        {

            // Content Element Model
            $this->objElement = $objElement;

            // Get allowed and valid files
            // Files must have been selected in the content element!
            $this->getValidFiles();

            // Get file-IDS from $_GET
            $arrIds = explode(',', \Input::get('files'));
            $error = 0;

            // validate
            foreach ($arrIds as $fileId)
            {
                $oFile = \FilesModel::findByPk($fileId);
                if ($oFile === null)
                {
                    \System::log('Couldn\'t find file with ID ' . $fileId . ' in tl_files. System stopped!', __METHOD__, TL_ERROR);
                    $error++;
                    continue;
                }

                if (!in_array($fileId, $this->arrValidFileIDS))
                {
                    \System::log('User is not allowed to download file ID ' . $fileId . ' (path: "' . $oFile->path . '"). System stopped!', __METHOD__, TL_ERROR);
                    $error++;
                    continue;
                }

                if (!is_file(TL_ROOT . '/' . $oFile->path))
                {
                    \System::log('File with ID ' . $fileId . ' (path: "' . $oFile->path . '") does not exists in the filesystem. System stopped!', __METHOD__, TL_ERROR);
                    $error++;
                    continue;
                }

                $this->arrFileIDS[] = $fileId;
            }

            // Error handling
            if ($error > 0)
            {
                header('HTTP/1.1 403 Forbidden');
                die_nicely('be_forbidden', 'Forbidden');
            }

            if (count($this->arrFileIDS) < 1)
            {
                \System::log('No valid files selected for the download!', __METHOD__, TL_ERROR);
                header('HTTP/1.1 403 Forbidden');
                die_nicely('be_forbidden', 'Forbidden');
            }


            // Send file to browser
            $this->sendZipFileToBrowser();
        }


        if (\Environment::get('isAjaxRequest') && \Input::get('ceDownloads') && \Input::get('loadLanguageData'))
        {
            $this->sendLanguageData();
        }

        return $strBuffer;
    }

    /**
     * Sort get valid and allowed files
     */
    protected function getValidFiles()
    {

        // Use the home directory of the current user as file source
        if ($this->objElement->useHomeDir && FE_USER_LOGGED_IN)
        {
            $objUser = \FrontendUser::getInstance();

            if ($objUser->assignDir && $objUser->homeDir)
            {
                $this->objElement->multiSRC = array($objUser->homeDir);
            }
        }
        else
        {
            $this->objElement->multiSRC = deserialize($this->objElement->multiSRC);
        }

        // Return if there are no files
        if (!is_array($this->objElement->multiSRC) || empty($this->objElement->multiSRC))
        {
            return;
        }

        // Get the file entries from the database
        $objFiles = \FilesModel::findMultipleByUuids($this->objElement->multiSRC);

        $files = array();

        $allowedDownload = trimsplit(',', strtolower(\Config::get('allowedDownload')));

        // Get all files
        while ($objFiles->next())
        {
            // Continue if the files has been processed or does not exist
            if (isset($files[$objFiles->path]) || !file_exists(TL_ROOT . '/' . $objFiles->path))
            {
                continue;
            }

            // Single files
            if ($objFiles->type == 'file')
            {
                $objFile = new \File($objFiles->path, true);

                if (!in_array($objFile->extension, $allowedDownload) || preg_match('/^meta(_[a-z]{2})?\.txt$/', $objFile->basename))
                {
                    continue;
                }

                // Add the file
                $files[$objFiles->path] = array
                (
                    'id' => $objFiles->id,
                );
                $this->arrValidFileIDS[] = $objFiles->id;
            }

            // Folders
            else
            {
                $objSubfiles = \FilesModel::findByPid($objFiles->uuid);

                if ($objSubfiles === null)
                {
                    continue;
                }

                while ($objSubfiles->next())
                {
                    // Skip subfolders
                    if ($objSubfiles->type == 'folder')
                    {
                        continue;
                    }

                    $objFile = new \File($objSubfiles->path, true);

                    if (!in_array($objFile->extension, $allowedDownload) || preg_match('/^meta(_[a-z]{2})?\.txt$/', $objFile->basename))
                    {
                        continue;
                    }


                    // Add the image
                    $files[$objSubfiles->path] = array
                    (
                        'id' => $objSubfiles->id,
                    );
                    $this->arrValidFileIDS[] = $objSubfiles->id;

                }
            }
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
     * @throws \Exception
     */
    protected function sendZipFileToBrowser()
    {

        // Delete old/unused zip-archives
        $this->deleteOldFolders();

        $time = time();

        // Set zip-archive name/path
        $zipFilePath = $this->archivePath . '/' . $time . '/archive_' . $time . '.zip';

        // Create zip-archive folder
        new \Folder($this->archivePath . '/' . $time, true);

        // Initialize archive object
        $zip = new \ZipWriter($zipFilePath);
        $i = 0;
        // Add files to zip-archive
        foreach ($this->arrFileIDS as $id)
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