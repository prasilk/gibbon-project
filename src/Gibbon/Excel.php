<?php
namespace Gibbon;

use Gibbon\Contracts\Database\Connection;
use Gibbon\Database\Result;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * Export to Spreadsheet
 *
 * @version	14th April 2016
 * @since	8th April 2016
 */
class Excel extends Spreadsheet
{
    private	$fileName;

    private function setHeader()//this function used to set the header variable
    {
        if(stristr($_SERVER['HTTP_USER_AGENT'], 'ipad') !== false or stristr($_SERVER['HTTP_USER_AGENT'], 'iphone') !== false or stristr($_SERVER['HTTP_USER_AGENT'], 'ipod') !== false) {
            header('Content-type: application/octet-stream');
        } else {
            header('Content-type: application/vnd.ms-excel');
        }

        header('Content-Disposition: attachment; filename="' . $this->fileName . '"');
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private", false);
    }
    /**
     * Export with Query. Will print output directly.
     *
     * @version	27th May 2016
     * @since	8th April 2016
     * @return	void
     */
    function exportWithQuery($result, $excel_file_name)
    {
        $this->defineWorkSheet($excel_file_name);
        $this->getProperties()->setTitle("Gibbon Query Dump");
        $this->getProperties()->setSubject("Gibbon Query Dump");
        $this->getProperties()->setDescription("Dump of Query Results generated by Gibbon");

        $rNum = 0;
        while($row = $result->fetch()) {
            $rNum++;
            if ($rNum === 1)
            {
                // Row 1 is the headers.
                $cNum = 0;
                foreach($row as $name=>$value )
                {
                    $cNum++ ;
                    $this->getActiveSheet()->setCellValueByColumnAndRow($cNum, $rNum, $name);
                }
                $this->getActiveSheet()->getStyle('1:1')->getFont()->setBold(true);
                $rNum++;
            }
            $cNum = 0;
            foreach($row as $value )
            {
                $cNum++;
                $this->getActiveSheet()->setCellValueByColumnAndRow($cNum, $rNum, $value);
            }
        }

        foreach(range(0, $cNum) as $col )
            $this->getActiveSheet()->getColumnDimensionByColumn($col)->setAutoSize(true);

        $this->exportWorksheet();
    }

    /**
     * define Worksheet
     *
     * @version	8th April 2016
     * @since	8th April 2016
     * @param	string	File Name
     * @return	void
     */
    public function defineWorksheet($fileName)
    {
        $this->getProperties()->setCreator(__("Gibbon"));
        $this->getProperties()->setLastModifiedBy(__("Gibbon"));
        $this->getProperties()->setDescription(__('This information is confidential. Generated by Gibbon (https://gibbonedu.org).'));
            $filename = 'No_Name_Set.xlsx';
        $this->fileName = $fileName;
        if (substr($this->fileName, strlen($this->fileName) - 4) === '.xls')
            $this->fileName .= 'x';
    }

    /**
     * export Worksheet
     *
     * @version	9th April 2016
     * @since	9th April 2016
     * @param	boolean	Use Excel2007 or >
     * @return	void
     */
    public function exportWorksheet($openXML = true)
    {
        // Instantiate a Writer to create an OfficeOpenXML Excel .xlsx file
        // Write the Excel file to filename some_excel_file.xlsx in the current directory
        if ($openXML) {
            $objWriter = IOFactory::createWriter($this, 'Xlsx');
        } else {
            $this->fileName = substr($this->fileName, 0, -1);
            $objWriter = IOFactory::createWriter($this, 'Xls');
        }
        // Write the Excel file to filename some_excel_file.xlsx in the current directory
        $this->setHeader();

        $objWriter->save('php://output');
        die();
    }

    /**
     * construct
     *
     * @version	9th April 2016
     * @since	9th April 2016
     * @param	string	File Name
     * @return	void
     */
    public function __construct($fileName = NULL)
    {
        parent::__construct();
        $this->defineWorksheet($fileName);
    }

    /**
     * cell Colour
     *
     * @version	11th April 2016
     * @since	11th APril 2016
     * @param	string	Cell/s
     * @param	string	Colour
     * @param	object	Chaining
     */
    public function cellColour($cells, $colour)
    {
        $this->getActiveSheet()->getStyle($cells)->getFill()->applyFromArray( array(
            'type' => Fill::FILL_SOLID,
            'startcolor' => array(
                'rgb' => $colour
            )
        ));
        return $this;
    }

    /**
     * cell Color  (American)
     *
     * @version	11th April 2016
     * @since	11th April 2016
     * @param	string	Cell/s
     * @param	string	Colour
     * @param	object	Chaining
     */
    public function cellColor($cells, $colour)
    {
        return $this->cellColour($cells, $colour);
    }

    /**
     * Estimate Cell Count in Spreadsheet
     *
     * @since	14th April 2016
     * @version	v23
     * @param	\Gibbon\Contracts\Database\Connection|\Gibbon\Database\Result $reference Reference for connection or result.
     *                                                                                   The use of Connection is deprecated.
     *
     * @return	int	Estimated Cell Count
     */
    public function estimateCellCount($reference)
    {
        if (!is_object($reference) && !($reference instanceof Connection) && !($reference instanceof Result)) {
            throw new \InvalidArgumentException('Argument must be an object of ' . Connection::class . ' or ' . Result::class);
        }
        if ($reference instanceof Result) {
            return $reference->columnCount() * $reference->rowCount();
        }
        /**
         * @var \Gibbon\Database\Connection $reference
         */
        // TODO: remove the direct use of Connection::getResult for it is obsoleted.
        if ($reference instanceof Connection && ($result = $reference->getResult()) !== NULL) {
            return $result->columnCount() * $result->rowCount();
        }
        return 0;
    }

    /**
     * cell Font Colour
     *
     * @version	14th April 2016
     * @since	14th APril 2016
     * @param	string	Cell/s
     * @param	string	Colour
     * @param	object	Chaining
     */
    public function cellFontColour($cells, $colour)
    {
        $styleArray = array(
            'font'  => array(
                'color' => array('rgb' => $colour),
            )
        );
        $this->getActiveSheet()->getStyle($cells)->applyFromArray($styleArray);
        return $this;
    }

    /**
     * cell Font Color  (American)
     *
     * @version	14th April 2016
     * @since	14th April 2016
     * @param	string	Cell/s
     * @param	string	Colour
     * @param	object	Chaining
     */
    public function cellFontColor($cells, $colour)
    {
        return $this->cellFontColour($cells, $colour);
    }

    /**
     * Get a column letter name for given number
     *
     * @version	v13
     * @since	v13
     * @param	int		number
     * @return	string	letter
     */
    public function num2alpha($n) {
        for($r = ""; $n >= 0; $n = intval($n / 26) - 1)
            $r = chr($n%26 + 0x41) . $r;
        return $r;
    }
}
?>
