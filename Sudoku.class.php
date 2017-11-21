<?php

/**********************************************************************
 * Copyright (c)- 2005 - Bronstee.com Software & Services and others.
 * All rights reserved.   This program and the accompanying materials
 * are made available under the terms of the
 * GNU General Public License (GPL) Version 2, June 1991,
 * which accompanies this distribution, and is available at:
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * Contributors:
 * Ghica van Emde Boas - original author, Sept 2005
 * Ton Meuleman - added the checkGrouping solution, March 2007
 * <contributor2> - <description of contribution>
 *     ...
 *********************************************************************/
require "Solver.class.php";

class Sudoku {
    protected $solver;
    protected $csvtext;
    protected $DLX;
    
    public function __construct() {
        $this->solver = new Solver();
       	
//        $this->grouping = new Grouping();
    }

    public function display() {
        $srv = $_SERVER['PHP_SELF'];
	$this->solver->getUndoValues();
        echo "<form name='sdkform' action='$srv' enctype='multipart/form-data' method='post'>\n";
        echo "<table align='center' class='outer'><tr>";
        for ($i = 1; $i < 10; $i ++) {
            echo '<td>';
            $this->displayBlock($i);
            echo '</td>';
            if ($i % 3 == 0) {
                echo '</tr></tr>'; // start a new row after 9 nfields
            }
        }
        echo '</tr></table>';

        echo "<div class='formbuttons'>\n";
        echo "<br/><input class='submit' type='submit' name='solve' value='value-check'/>\n";
        echo "<input class='submit' type='submit' name='unique-check' value='unique-check'/>\n";
        echo "<input class='submit' type='submit' name='RC-check' value='RC-check'/>\n";
        //echo "<input class='submit' type='submit' name='pair-check' value='pair-check'/>\n";
        echo "<input class='submit' type='submit' name='grouping' value='groups-check'/>\n";
	echo "<input class='submit' type='submit' name='wings' value='wing-check'/>\n";
	echo "<input class='submit' type='submit' name='chains' value='chain-check'/>\n";
	echo "<input class='submit' type='submit' name='DLX' value='DLX Solution'/>\n";
        //        echo "<input class='submit' type='submit' name='force' value='force'/>\n";
        //        echo "<br/><input class='submit' type='submit' name='load' value='load'/>\n";
        echo "<br/><input class='submit' type='submit' name='showcsv' value='show csv'/>\n";
        echo "<input class='submit' type='button' name='help' value='help' ";
        echo " onclick=\"launchhelp();\"/>\n";
        echo "<br/><input class='submit' type='submit' name='empty' value='clear'/>\n";
        echo "<input class='submit' type='submit' name='reset' value='reset'/>\n";
        echo "<input class='submit' type='submit' name='undo' value='undo'/>\n";
        echo "<br/><br/><input class='submit' type='submit' name='upload' value='upload'/>\n";
        echo "<input class='browse' type='file' name='uploadfile'  />\n";
        echo "</div>\n";
        echo '</form>';
    }

    public function displayBlock($blockno) {
        echo "<table class='inner'><tr>\n";
        $i = 0;
	foreach ($this->solver->nfields as $field) {
            if ($field->block == $blockno) {
                echo $field->displayField();
                $i ++;
                if ($i % 3 == 0) {
                    echo "</tr><tr>\n"; // start a new row after 3 nfields
                }
            }
        }
        echo "</tr></table>\n";
    }

    public function checkAction() {
	if (isset ($_POST['showcsv'])) $this->savecsv();
        elseif (isset ($_POST['empty'])) $this->emptyFields();
        elseif (isset ($_POST['reset'])) $this->solver->resetFields();
        elseif (isset ($_POST['undo'])) $this->solver->undo();
        elseif (isset ($_POST['upload'])) $this->readupload();
        elseif (isset ($_POST['solve'])) $this->solveIt(); // value check
        elseif (isset ($_POST['unique-check'])) $this->solveUnique();
        elseif (isset ($_POST['RC-check'])) $this->solveRC();
        //elseif (isset ($_POST['pair-check'])) $this->solvePairs();
	elseif (isset ($_POST['chains'])) $this->solveWithChains();
        elseif (isset ($_POST['grouping'])) $this->solveGrouping();
        elseif (isset ($_POST['force'])) $this->solveWithForce();
	elseif (isset ($_POST['wings'])) $this->solveWithWings();
	elseif (isset ($_POST['DLX'])) $this->solveWithDLX();
        else {
            //          echo "initial start...";
            $this->solver->emptyFields();
        }
    }

    public function solveWithDLX(){
	$this->solver->solveDLX();
    }
    
    public function solveWithWings() {
        $this->solver->checkWings();
	$this->solver->checkValues();
    }

    public function solveWithChains() {
        $this->solver->checkChains();
	$this->solver->checkValues();
    }
    
    public function solveGrouping() {
        //$this->solveUnique();
        //$this->solveRC();
        //$this->solvePairs();
        $this->solver->checkGrouping();
	$this->solver->checkValues();
    }

    public function solveWithForce() {
        echo "you have pressed solve with force";
    }

    public function emptyFields() {
        $this->solver->emptyFields();
        unset($_SESSION['undostack']);
    }

    public function solveRC() {
        //        echo "you have pressed: do row-column check.";
        //$this->solveUnique();
        $this->solver->checkDouble();
	$this->solver->checkValues();
    }

    //public function solvePairs() {
        //        echo "you have pressed: do pair check.";
        //$this->solveUnique();
        //$this->solver->checkPairs();
	//$this->solver->checkValues();
    //}

    public function solveIt() {
        //            echo "you have pressed submit";
        $this->solver->checkValues();
    }

    public function solveUnique() {
        //        echo "you have pressed: check for unique values";
        //$this->solver->checkValuesRecurse();
        $this->solver->checkUnique('row');
        $this->solver->checkUnique('column');
        $this->solver->checkUnique('block');
	$this->solver->checkValues();
    }

    public function readupload() {
        //echo 'read upload file';
        $uploaddir = "./temp";
        if (!is_dir($uploaddir)) {
            trigger_error("You need to create a directory: ".$uploaddir, E_USER_NOTICE);
        }

        $uploadfile = $uploaddir.'/'.$_FILES['uploadfile']['name'];
        if (move_uploaded_file($_FILES['uploadfile']['tmp_name'], $uploadfile)) {
            $filename = $uploadfile;
        } else {
            trigger_error("no upload file found, or not writeable: ".$filename, E_USER_NOTICE);
            return;
        }
        //echo '<br/>read uploaded file: ', $filename;
        $this->emptyFields();
        $fp = fopen($filename, "r");
        $pos = 1;
        while ($data = fgetcsv($fp, filesize($filename), ",")) {
            for ($i = 0; $i < 9; $i ++) {
		if ($pos <= 81){
		    $field = $this->solver->nfields[$pos];
		    if (trim($data[$i]) != '') {
			$field->setValue($data[$i]);
		    }else{
			$field->possibleValues = range(1,9);
		    }
		}
                $pos ++;
            }
        }
        fclose($fp);
        unlink($filename); // delete the temporary file
	$this->solver->checkValuesOnly();
    }

    public function savecsv() {
        $filename = 'sdkdef2.txt';
        echo "<br>save the text below to a loacal file...";
        $this->createcsv();
        echo "<hr><pre>$this->csvtext</pre><hr>";
    }

    protected function createcsv() {
        $i = 0;
        $this->csvtext = '';
        foreach ($this->solver->nfields as $field) {
            $i ++;
            $this->csvtext .= "$field->fieldValue, ";
            if ($i % 9 == 0) {
                $this->csvtext .= "\n";
            }
        }
    }

    protected function writecsv($filename) {

        if (!$handle = fopen($filename, 'wb')) {
            echo "Cannot open file ($filename)";
            exit;
        }
        if (fwrite($handle, $this->csvtext) === FALSE) {
            echo "Cannot write to file ($filename)";
            exit;
        }
        fclose($handle);

    }

}
?>
