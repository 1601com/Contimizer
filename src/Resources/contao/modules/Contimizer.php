<?php

namespace agentur1601com\Contimizer\modules;

use agentur1601com\Contimizer\classes\ContimizerC;

class Contimizer extends \BackendModule
{
    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'be_contimizer';

    /**
     * Generate the module
     */
    protected function compile()
    {
        \System::loadLanguageFile('tl_modules');
        $this->Template = new \BackendTemplate('be_contimizer');
        $this->Template->request = ampersand($this->Environment->request,true);

        $this->Template->headlineExport = $GLOBALS['TL_LANG']['MSC']['contimizer']['export']['headline'];
        $this->Template->textExport = $GLOBALS['TL_LANG']['MSC']['contimizer']['export']['text'];
        $this->Template->buttonExport = $GLOBALS['TL_LANG']['MSC']['contimizer']['export']['button'];

        $this->Template->headlineImport = $GLOBALS['TL_LANG']['MSC']['contimizer']['import']['headline'];
        $this->Template->textImport = $GLOBALS['TL_LANG']['MSC']['contimizer']['import']['text'];
        $this->Template->buttonImport = $GLOBALS['TL_LANG']['MSC']['contimizer']['import']['button'];

        $this->Template->js = "bundles/contimizer/upload.js";

        if(\Input::post('type') && \Input::post('type') === "export")
        {
            $this->exportSEO();
        }
        elseif (\Input::post('type') && \Input::post('type') === "import")
        {
             $this->insertSEO($_FILES['importCSV']["tmp_name"],\Input::post('mailaddress'));
        }
    }

    /**
     *
     */
    private function exportSEO()
    {
        $ContimizerCI = new ContimizerC();
        $this->Template->info = $ContimizerCI->exportSEO();
    }

    /**
     * @param $file
     * @param $email
     * @return bool
     * @throws \Exception
     */
    private function insertSEO($file,$email = false)
    {
        $ContimizerCI = new ContimizerC();

        /*Check if upload-File exists*/
        if(empty($file))
        {
            $this->Template->info = $ContimizerCI->getInfoAsHTML("div","",$ContimizerCI->setInfo($GLOBALS['TL_LANG']['MSC']['contimizer']['file']['empty'],"'error'"));
            return false;
        }

        /*Create a backup before import*/
        if(!$ContimizerCI->createBackupTable()){
            $ContimizerCI->sendMail($email,$GLOBALS['TL_LANG']['MSC']['contimizer']['db']['noBackup'],$GLOBALS['TL_LANG']['MSC']['contimizer']['email']['text']);
            return false;
        }

        /*parse the import file*/
        if($ContimizerCI->setDataByFile($file))
        {
            /*validate data*/
            if(!$ContimizerCI->validateImport())
            {
                $this->Template->info = $ContimizerCI->getInfoAsHTML();
                return false;
            }
            /*import data into databse*/
            $ContimizerCI->importSEO();

            /*send mail*/
            $ContimizerCI->sendMail($email,$GLOBALS['TL_LANG']['MSC']['contimizer']['email']['subject'],$GLOBALS['TL_LANG']['MSC']['contimizer']['email']['text']);

            /*set infos*/
            $this->Template->info = $ContimizerCI->getInfoAsHTML();
            return true;
        }
        return false;
    }
}