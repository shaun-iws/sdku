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
 * <contributor2> - <description of contribution>
 *     ...
 *********************************************************************/
require "Solver.class.php";

class Sudoku {
    var $solver;
    var $csvtext;

    function Sudoku() {
        $this->solver = &new Solver();
    }

    function getUndoValues() {
        $fields = $this->solver->getFields();
        foreach($fields as $field) {
            $seq = $field->seqno;
            $us[$seq] = implode(' ', $field->possibleValues);
        }
        $_SESSION['undostack'][] = $us;
    }


    function display() {
//        $nfields = $this->solver->getFields();
//        foreach ($nfields as $field) {
//            echo "<br/>display field: ", $field->seqno, " value: ", $field->fieldValue;
//        }
        $srv = $_SERVER['PHP_SELF'];
        $this->getUndoValues();
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
        echo "<input class='submit' type='submit' name='pair-check' value='pair-check'/>\n";
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

    function displayBlock($blockno) {
        echo "<table class='inner'><tr>\n";
        $i = 0;
        $nfields = $this->solver->getFields();
        foreach ($nfields as $field) {
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

    function checkAction() {
        if ($_POST['load'] == 'load')
            $this->loadcsv();
        elseif (isset ($_POST['showcsv'])) $this->savecsv();
        elseif (isset ($_POST['empty'])) $this->emptyFields();
        elseif (isset ($_POST['reset'])) $this->solver->resetFields();
        elseif (isset ($_POST['undo'])) $this->solver->undo();
        elseif (isset ($_POST['upload'])) $this->readupload();
        elseif (isset ($_POST['solve'])) $this->solveIt();
        elseif (isset ($_POST['unique-check'])) $this->solveUnique();
        elseif (isset ($_POST['RC-check'])) $this->solveRC();
        elseif (isset ($_POST['pair-check'])) $this->solvePairs();
        elseif (isset ($_POST['force'])) $this->solveWithForce();
        else {
            //          echo "initial start...";
            $this->solver->emptyFields();
        }
    }

    function solveWithForce() {
        echo "you have pressed solve with force";
    }

    function emptyFields() {
        $this->solver->emptyFields();
        unset($_SESSION['undostack']);
    }

    function solveRC() {
        //        echo "you have pressed: do row-column check.";
        $this->solveUnique();
        $this->solver->checkDouble();
    }

    function solvePairs() {
        //        echo "you have pressed: do pair check.";
        $this->solveUnique();
        $this->solver->checkPairs();
    }

    function solveIt() {
        //            echo "you have pressed submit";
        $this->solver->checkValues();
    }

    function solveUnique() {
        //        echo "you have pressed: check for unique values";
//        $this->solver->checkValuesRecurse();
        $this->solver->checkUnique('row');
        $this->solver->checkUnique('column');
        $this->solver->checkUnique('block');
    }

    function loadcsv() {
        $this->emptyFields();
        $filename = 'sdkdef2.csv';
        $fp = fopen($filename, "r");
        $pos = 1;
        while ($data = fgetcsv($fp, filesize($filename), ",")) {
            for ($i = 0; $i < 9; $i ++) {
                if (trim($data[$i]) != '') {
                    $field = $this->solver->getField($pos);
                    $field->setValue($data[$i]);
                    $field->possibleValues = array ($data[$i]);
                    $seqno = $field->seqno;
                    $this->solver->nfields[$seqno-1] = $field;
                }
                $pos ++;
            }
        }
        fclose($fp);

    }

    function readupload() {
        //        echo 'read upload file';
        $uploaddir = "./temp";
        if (!is_dir($uploaddir)) {
            trigger_error("You need to create a directory: ".$uploaddir, E_USER_ERROR);
        }

        $uploadfile = $uploaddir.'/'.$_FILES['uploadfile']['name'];
        if (move_uploaded_file($_FILES['uploadfile']['tmp_name'], $uploadfile)) {
            $filename = $uploadfile;
        } else {
            trigger_error("no upload file found, or not writeable: ".$filename, E_USER_ERROR);
            return;
        }
//        echo '<br/>read uploaded file: ', $filename;
        $this->emptyFields();
        $fp = fopen($filename, "r");
        $pos = 1;
        while ($data = fgetcsv($fp, filesize($filename), ",")) {
            for ($i = 0; $i < 9; $i ++) {
                if (trim($data[$i]) != '') {
                    $field = $this->solver->getField($pos);
                    $field->setValue($data[$i]);
                    $field->possibleValues = array ($data[$i]);
                    $seqno = $field->seqno;
                    $this->solver->nfields[$seqno-1] = $field;
                }
                $pos ++;
            }
        }
        fclose($fp);
        unlink($filename); // delete the temporary file
    }

    function savecsv() {
        $filename = 'sdkdef2.txt';
        echo "<br>save the text below to a loacal file...";
        $this->createcsv();
        echo "<hr><pre>$this->csvtext</pre><hr>";
    }

    function createcsv() {
        $i = 0;
        $this->csvtext = '';
        foreach ($this->solver->getFields() as $field) {
            $i ++;
            $this->csvtext .= "$field->fieldValue, ";
            if ($i % 9 == 0) {
                $this->csvtext .= "\n";
            }
        }
    }

    function writecsv($filename) {

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