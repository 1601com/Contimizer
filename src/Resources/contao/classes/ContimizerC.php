<?php
namespace agentur1601com\Contimizer\classes;

use Contao\Email;

class ContimizerC extends \Backend
{

    /**
     * Contains all errors and notices
     * @var array
     */
    private $infos = [];

    /**
     * Export table name
     * @var string
     */
    private $table;

    /**
     * Table backup name
     * @var string
     */
    private $tableBackup;

    /**
     * CSV export file name
     * @var string
     */
    private $exportFile;

    /**
     * Databse Instance
     * @var \Contao\Database
     */
    private $Database;

    /**
     * Contains all data after importSEO()
     * @var array
     */
    private $importData = [];

    public function __construct($table = "tl_page")
    {
        $this->infos = [];
        $this->table = $table;
        $this->tableBackup = $this->table."_".date("Y_m_d_H_i_s")."_backup";
        $this->exportFile = $_SERVER['SERVER_NAME']."_".$_GET['db_name'].".".$table."_".date("ymd_H_i",time())."_1SRV.csv";
        $this->Database = \Database::getInstance();
        $this->importData = [];
    }

    /**
     * Export the SEO-Texte and Databse-Information
     */
    public function exportSEO()
    {
        header("Content-Type: application/csv; charset=UTF-8;");
        header("Content-Disposition: attachment; filename=\"$this->exportFile\"");
        $get = ['id','alias','if (pageTitle = "", `title`, `pageTitle`) as pageTitle','description','robots','sitemap'];
        $getString = $this->DBCreateSelectString($get);
        $this->Database->query("SET NAMES utf8");
        $result = $this->Database->prepare("SELECT $getString FROM `{$this->table}` WHERE `type` = ?;")->execute('regular');
        $arrResult = $result->fetchAllAssoc();
        echo mb_convert_encoding($this->arrayToCSV($arrResult),"ISO-8859-1");
        die();
    }

    /**
     * @param $file
     * @return bool
     */
    public function setDataByFile($file)
    {
        if(isset($file) && !empty($file))
        {
            $handle = fopen($file, "r");
        }
        else
        {
            return false;
        }

        while (($data = fgetcsv($handle,0, ";")) !== FALSE)
        {
            array_push($this->importData, array(
                    "id" => $data[0],
                    "alias" => $data[1],
                    "pageTitle" => $data[2],
                    "description" => $data[3],
                    "robots" => $data[4],
                    "sitemap" => $data[5]
                )
            );
        }
        fclose($handle);
        return true;
    }

    /**
     * @return array
     */
    public function getImportData()
    {
        return $this->importData;
    }

    /**
     * Import data into databse
     * @return bool
     */
    public function importSEO()
    {
        if(!is_array($this->importData) || empty($this->importData))
        {
            $this->setInfo("No Data to import");
            return false;
        }

        foreach ($this->importData as $data)
        {
            $set = ['alias' => $data['alias'], 'pageTitle' => $data['pageTitle'], 'description' => $data['description'], 'robots' => $data['robots'], 'sitemap' => $data['sitemap']];
            $this->Database->prepare("UPDATE `{$this->table}` %s WHERE `id` = ?")->set($set)->execute($data['id']);
        }

        $this->setInfo("Import was correct","success");
        return true;

    }

    /**
     * Validate the import data array and create infos & errors
     * @return bool
     */
    public function validateImport()
    {
        if(!is_array($this->importData)){
            return false;
        }

        foreach ($this->importData as $key => &$data)
        {
            if($data['id'] == 'id')
            {
                unset($data);
                continue;
            }

            if(!is_array($data))
            {
                $this->setInfo("One line is missing!","error");
                return false;
            }

            if(empty($data['id']) || empty($data['alias']))
            {
                $this->setInfo("Value \"id\" or \"alias\" form ID: ".$data['id']." or from alias: ".$data['alias']." is empty!","error");
                return false;
            }

            if(
                ($data['robots'] === 'index,follow'      && $data['sitemap'] !== 'map_default') ||
                ($data['robots'] === 'noindex,nofollow'  && $data['sitemap'] !== 'map_never') ||
                ($data['robots'] === 'noindex,follow'    && $data['sitemap'] !== 'map_never')
            )
            {
                $this->setInfo("I think there is an mistake with the sitemap and robots on ID: " . $data['id']);
            }

            if($data['pageTitle'] === '')
            {
                $this->setInfo("PageTitle is empty on ID: " . $data['id']);
            }

            if($data['description'] === '')
            {
                $this->setInfo("Description is empty on ID: " . $data['id']);
            }

            foreach ($data as &$value)
            {
                $value = \Input::xssClean($value);
            }
        }
        return true;
    }

    /**
     * Create new info
     * @param $message
     * @param string $type
     * @return int
     */
    public function setInfo($message, $type = "info")
    {
        array_push($this->infos,['message' => $message,'type' => $type]);
        return count($this->infos)-1;
    }

    /**
     * Return all errors and infos as html-string
     * @param string $element
     * @param string $class
     * @param null $nr
     * @return string
     */
    public function getInfoAsHTML($element = 'div', $class = '', $nr = null)
    {
        if(!is_array($this->infos))
        {
            return "";
        }

        if($nr)
        {
            return "<{$element} class='{$this->infos[$nr]['type']} $class'>{$this->infos[$nr]['message']}</{$element}>";
        }

        $returnString = "";
        foreach ($this->infos as $html)
        {
            $returnString.= "<{$element} class='{$html['type']} $class'>{$html['message']}</{$element}>";
        }
        return $returnString;
    }

    /**
     * @param $array
     * @return bool|string
     */
    private function DBCreateSelectString($array)
    {
        $string = "";
        if(!is_array($array)){
            return false;
        }
        $i=0;
        foreach ($array as $getParam){
            $string .= $getParam;
            if($i < count($array)-1){
                $string .= ',';
            }
            $i++;
        }
        return $string;
    }

    /**
     * Convert array to csv sting
     * @param array $array
     * @return string
     */
    private function arrayToCSV(array $array):string
    {
        if(!is_array($array)){
            return false;
        }
        $string = "";
        foreach ($array as $row)
        {
            if(!is_array($row)){
                return false;
            }
            $i = 0;
            foreach($row as $cell)
            {
                $cell = str_replace(["\n","\r",";",'"','\'','\\'],[' ','','','','',''],$cell);
                $string .= "\"$cell\"";
                if($i !== count($row)-1)
                {
                    $string .= ';';
                }
                $i++;
            }
            $string .= "\n";
        }
        return $string;
    }

    /**
     * Create a backup for import
     * @return bool
     */
    public function createBackupTable()
    {
        $this->Database->query("CREATE TABLE `{$this->tableBackup}` LIKE `{$this->table}`");
        $this->Database->query("INSERT INTO `{$this->tableBackup}` SELECT * FROM `{$this->table}`");
        return true;
    }

    /**
     * Send E-Mail
     * @param $toEmail
     * @param $subject
     * @param $text
     * @return bool
     * @throws \Exception
     */
    public function sendMail($toEmail,$subject,$text)
    {
        if(!$toEmail){
            return false;
        }

        $email = new Email();
        $email->__set("subject",$subject);
        $email->__set("text",sprintf($text,print_r($this->infos,true)));
        $email->sendTo($toEmail);

        return true;
    }

}