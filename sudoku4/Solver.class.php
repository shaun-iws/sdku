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
require "NumberField.class.php";

class Solver {
    var $nfields = array ();
    var $csvtext;
    var $valueFound = true;

    /*
     * create the 9x9 grid of number fields
     */
    function Solver() {
        for ($i = 1; $i < 82; $i ++) {
            $name = "f$i";
            $pstring = trim($_POST[$name]);
            $this->nfields[] = &new NumberField($name, "$i", $pstring);
        }
    }

    function setUndoValues($us) {
        $this->emptyFields();
        foreach ($us as $seq => $pstring) {
            $name = "f$seq";
            $pstring = trim($pstring);
            if ($pstring != '') {
                $field = $this->getField($seq);
                $pvals = explode(' ', $pstring);
                $cnt = count($pvals);
                if($cnt == 1) $field->setValue($pvals[0]);
                $field->possibleValues = $pvals;
                $seqno = $field->seqno;
                $this->nfields[$seqno-1] = $field;
            }
        }
    }

    /*
     * clear all the values in the grid
     */
    function clearFields() {
        foreach ($this->nfields as $field) {
            $field->setValue('');
            $field->possibleValues = range(1, 9);
            $seqno = $field->seqno;
            $this->nfields[$seqno-1] = $field;
        }
    }

    /*
     * show blank fields
     */
    function emptyFields() {
        foreach ($this->nfields as $field) {
            $field->setValue('');
            $seqno = $field->seqno;
            $this->nfields[$seqno-1] = $field;
        }
//        foreach ($this->nfields as $field) {
//            $dval = implode(' ', $field->possibleValues);
////            echo "<br/>field: ", $field->seqno, " value: ", $field->fieldValue, " pvals: $dval";
//        }
    }

    /*
     * go back to initial state
     */
    function resetFields() {
        if (count($_SESSION['undostack'])>0){
            $this->setUndoValues($_SESSION['undostack'][0]);
            unset($_SESSION['undostack']);
        }
        else echo "<br/>no reset data available.";
    }

    /*
     * show blank fields
     */
    function undo() {
        if (count($_SESSION['undostack'])>1){
            $last = array_pop($_SESSION['undostack']);
            $last = array_pop($_SESSION['undostack']);
            $this->setUndoValues($last);
        }
        else {
            echo "<br/>nothing to undo!";
            $this->resetFields();
        }
    }

    /*
     * find the field that corresponds to a certain position
     */
    function &getField($pos) {
        return $this->nfields[$pos -1];
    }

    function &getFields() {
        return $this->nfields;
    }

    function setFields(&$fields) {
        $this->nfields = $fields;
    }

    function getRCB($nr, $type) {
        $rcb = array ();
        foreach ($this->nfields as $field) {
            if ($field-> $type == $nr)
                $rcb[] = $field->seqno;
        }
        return $rcb;
    }

    /*
     * Check the possible values for all fields
     */
    function checkValues() {
        //        echo "<br/>checking values";
        $this->checkFieldSet(range(1,81), false);
    }

   /*
     * Check the possible values for all fields
     */
    function checkValuesRecurse() {
        do {
//            echo "<br/>checking values";
            $this->valueFound = false;
            $this->checkFieldSet(range(1,81), true);
        } while ($this->valueFound);
    }

    /*
     * Check the possible values for a set of fields
     */
    function checkFieldSet($fieldnos, $recurse) {
        foreach ($fieldnos as $fno) {
            $field = $this->nfields[$fno-1];
            if ($field->hasValue())
                continue;
            $pvals = implode(',', $field->possibleValues);   
//            echo "<br/>checking: $field->seqno - $field->row, $field->column - pvals: $pvals";
            $this->checkSet('row',$fno, $recurse);
            $this->checkSet('column', $fno, $recurse);
            $this->checkSet('block', $fno, $recurse);
        }
    }

    function finishCheck() {
        //        echo "<br/>checking values";
        $i = 0;
        while ($this->valueFound && $i < 10) {
            $this->valueFound = false;
            $i ++;
            foreach (range(1,81) as $fno) {
            $this->checkSet('row',$fno);
            $this->checkSet('column', $fno);
            $this->checkSet('block', $fno);
            }
            //            echo "<br/>check values: $i times";
        }
    }

    function checkUnique($type) {
//        echo "<br/>checking unique $type";
        for ($i = 1; $i < 10; $i ++) {
            $this->checkValuesRecurse();
            $occurVals = array (0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
            $fieldnos = $this->getRCB($i, $type);
            foreach ($fieldnos as $fno) {
                $field = $this->nfields[$fno-1];
                for ($j = 1; $j < 10; $j ++) {
                    if (!$field->hasValue() && in_array($j, $field->possibleValues)) {
                        $occurVals[$j]++;
                    }
                }
            }
            // is there a unique one?
            $occ = implode(' ', $occurVals);
//            echo "<br/>uniquecheck: $type no: $i, occurs: $occ ";
            $this->checkOccursOnce($fieldnos, $occurVals);
        }
    }

    function checkOccursOnce($fieldnos, $occurVals) {
        $occ = implode(' ', $occurVals);
        for ($j = 1; $j < 10; $j ++) {
            if ($occurVals[$j] == 1) {
                foreach ($fieldnos as $fno) {
                    $field = $this->nfields[$fno-1];
                    $vals = implode(' ', $field->possibleValues);
                    if (in_array($j, $field->possibleValues)) {
                        $field->possibleValues = array($j);
                        $this->setValue($field, $j);
                        $this->valueFound = true;
                        break;
                    }
                }
            }

        }
    }

    function checkDouble() {
        //      echo "<br/>checking double values in rows, columns or blocks";
        for ($i = 1; $i < 10; $i ++) {
            //            $this->checkValues();
            $occurVals = array (0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
//            $uFields = $this->getRCB($i, 'block');
            $fieldnos = $this->getRCB($i, 'block');
            foreach ($fieldnos as $fno) {
                $field = $this->nfields[$fno-1];
 //           foreach ($uFields as $field) {
                for ($j = 1; $j < 10; $j ++) {
                    if (!$field->hasValue() && in_array($j, $field->possibleValues)) {
                        $occurVals[$j]++;
                    }
                }
            }
            // is there a unique one?
            $occ = implode(' ', $occurVals);
//            echo "<br/>doublecheck: block no: $i, occurs: $occ ";
            $this->checkOccursDouble($fieldnos, $occurVals);
        }
    }

    function checkOccursDouble($fieldnos, $occurVals) {
        $occ = implode(' ', $occurVals);
        // look through all numbers
        for ($j = 1; $j < 10; $j ++) {
            if ($occurVals[$j] > 1) {
                $val = $occurVals[$j];
                $rows = Array ();
                $cols = Array ();
                            $fieldnos = $this->getRCB($i, $type);
                foreach ($fieldnos as $fno) {
                    $field = $this->nfields[$fno-1];
                
//                foreach ($uFields as $field) {
                    $vals = implode(' ', $field->possibleValues);
                    if (in_array($j, $field->possibleValues)) {
                        $rows[] = $field->row;
                        $cols[] = $field->column;
                    }
                }
                $firstR = $rows[0];
                $firstC = $cols[0];
                $rEqual = true;
                $cEqual = true;
                foreach ($rows as $row) {
                    if ($row != $firstR) {
                        $rEqual = false;
                        break;
                    }
                }
                foreach ($cols as $col) {
                    if ($col != $firstC) {
                        $cEqual = false;
                        break;
                    }
                }
                if ($rEqual) {
//                    echo "<br/>$j occurs $val times";
//                    echo '<br/> rows: ', implode(' ', $rows);
                    // find the fields in the row, but not in the block
                    $ifieldnos = $this->getRCB($firstR, 'row');
                    $rowFieldnos = array_diff($ifieldnos, $fieldnos);
                    $this->diffOneValue($rowFieldnos, $j);
                }
                if ($cEqual) {
//                    echo "<br/>$j occurs $val times";
//                    echo '<br/> columns: ', implode(' ', $cols);
                    // find the fields in the column, but not in the block
                    $ifieldnos = $this->getRCB($firstR, 'column');
                    $colFieldnos = array_diff($ifieldnos, $fieldnos);
                    $this->diffOneValue($colFieldnos, $j);
                }
            }

        }
    }

    function diffOneValue($fieldnos, $value) {
        $valArray[] = $value;
          foreach ($fieldnos as $fno) {
            $rfield = $this->nfields[$fno-1];
            // if the field has a value, this value will be the only one in possibleValues
            if (!$rfield->hasValue()) {
                $rfield->possibleValues = array_values(array_diff($rfield->possibleValues, $valArray));
                $seqno = $rfield->seqno;
                $this->nfields[$seqno-1] = $rfield;
                if (count($rfield->possibleValues) == 1) {
                    $this->setValue($rfield, $rfield->possibleValues[0]);
                    break;
                }
            }
        }
    }


    function checkSet($type, $fno, $recurse) {
        $field = $this->nfields[$fno-1];
        if ($field->hasValue())
            return;
        $sno = $field->$type;
        $fieldnos = $this->getRCB($sno, $type);
        $sfieldnos = array_diff($fieldnos, array ($field->seqno));
        $this->diffValues($sfieldnos, $fno, $recurse);
    }

    function diffValues($fieldnos, $fno, $recurse) {
        $field = $this->nfields[$fno-1];
        foreach ($fieldnos as $rno) {
            $rfield = $this->nfields[$rno-1];
            // if the field has a value, this value will be the only one in possibleValues
            if ($rfield->hasValue()) {
                $field->possibleValues = array_values(array_diff($field->possibleValues, $rfield->possibleValues));
                $seqno = $field->seqno;
                $this->nfields[$seqno-1] = $field;
            }
            if (count($field->possibleValues) == 1) {
                if ($recurse) 
                    $this->setValue($field, $field->possibleValues[0]);
                else {
                    $field->setValue($field->possibleValues[0]);
                    $seqno = $field->seqno;
                    $this->nfields[$seqno-1] = $field;
                }
                break;
            }
        }
    }

    function checkPairs() {
        $this->checkPairsByType('row');
        $this->checkPairsByType('column');
        $this->checkPairsByType('block');
    }

    function checkPairsByType($type) {
//        echo "<br/>checking for pairs in ${type}s";
        for ($i = 1; $i < 10; $i ++) {
            //            $this->checkValues();
            $occurVals = array (0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
            $fieldnos = $this->getRCB($i, $type);
            $pairs = array();
            foreach ($fieldnos as $fno) {
                $field = $this->nfields[$fno-1];
                // is there a pair?
                if (count($field->possibleValues) == 2) {
                    $pval = implode(' ', $field->possibleValues);
//                    echo "<br/>paircheck: $type no: $i, pair: $pval ";
                    $pairs[] = array($pval, $field);
                }
            }
            if (count($pairs) > 1) {
//                echo "<br/>paircheck: $type no: $i";
                foreach ($pairs as $pair) {
                    $fieldStr = $pair[1]->toString();
//                    echo "<br/>$pair[0] -- $fieldStr";
                    $pstr = $pair[0];
                    foreach ($pairs as $ipair) {
                        if ($pstr == $ipair[0] && $pair[1]->seqno != $ipair[1]->seqno) {
//                            echo "<br/>found pairs: ", $pair[1]->toString(), " and: ", $ipair[1]->toString();
                            $fld1 = $pair[1];
                            $fld2 = $ipair[1];
                            $pfieldnos = array_diff($fieldnos, array($fld1->seqno, $fld2->seqno));
                            $this->diffOneValue($pfieldnos, $fld1->possibleValues[0]);
                            $this->diffOneValue($pfieldnos, $fld1->possibleValues[1]);
                        }
                    }
                    
                }
            }
        }
    }

    function setValue($field, $value) {
        $field->setValue($value);
        $seqno = $field->seqno;
        $this->nfields[$seqno-1] = $field;
        $this->valueFound = true;
//        $rno = $field->row;
//        $rfields = $this->getRCB($rno, 'row');
//        $this->checkFieldSet($rfields, true);
//        $cno = $field->column;
//        $cfields = $this->getRCB($cno, 'column');
//        $this->checkFieldSet($cfields, true);
//        $bno = $field->block;
//        $bfields = $this->getRCB($bno, 'block');
//        $this->checkFieldSet($bfields, true);
    }

}
?>