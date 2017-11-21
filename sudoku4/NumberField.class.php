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

class NumberField {
    var $fieldName;
    var $fieldValue;
    var $seqno;
    var $possibleValues;
    var $block;
    var $column;
    var $row;
    var $newValue = false;

    function NumberField($name, $value, $pstring) {
        $this->fieldName = $name;
        $this->fieldValue = $value;
        $this->seqno = $value;
        $this->column = (($value -1) % 9) + 1;
        $this->row = (int) ceil($value / 9);
        $blockrow = (int) ceil($this->row / 3);
        $this->block = (($blockrow -1) * 3) + (int) ceil($this->column / 3);
        $pvals = explode(' ', $pstring);
        $cnt = count($pvals);
        if (count($pvals) == 1 && trim($pstring != '')) {
            $this->fieldValue = $pstring;
            $this->possibleValues = array ($pstring);
        }
        elseif (count($pvals) > 1) {
            $this->fieldValue = '';
            $this->possibleValues = $pvals;
        } else {
            $this->fieldValue = '';
            $this->possibleValues = range(1, 9);
        }
        //         echo "<br/>constructing: $this->seqno - $pstring, count: $cnt";
        //         echo " - fieldval: ", $this->fieldValue, " - posval: ", implode(' ', $this->possibleValues);
    }

    function displayField() {
        $fieldValue = $this->fieldValue;
        $fieldName = $this->fieldName;
        $column = $this->column;
        $row = $this->row;
        $block = $this->block;
        $seqno = $this->seqno;
        if ($this->hasValue())
            if ($this->newValue) $txttype = 'rtype';
            else $txttype = 'ltype';
        else
            $txttype = 'stype';
//        echo "<br/>$seqno: $txttype";    
        $dval = implode(' ', $this->possibleValues);
        if (trim($dval) == '') $txttype = 'ltype';  
        if ($txttype == 'stype') $divclass = 'scell' ;
        else $divclass = 'ncell'; 
        $fld = "<td class='$txttype'><div class='$divclass' id='$seqno' onclick='fieldClick(this);'>" ;
        if (trim($dval) == '') { 
        $fld .= "<input tabindex='$seqno' class='ltype' type='text'" .
                " id='i$seqno' name='$fieldName' value='$dval'/>";
        } else {
        $fld .= $dval.
                "<input tabindex='$seqno' class='$txttype' type='hidden'" .
                " id='i$seqno' name='$fieldName' value='$dval'/>";
                
        }
        $fld .= "</div></td>\n";
        return $fld;
    }

    function hasValue() {
        if (trim($this->fieldValue) != '')
            return TRUE;
        else
            return FALSE;
    }

    function setValue($value) {
        if (trim($value) == '' && $_POST['submit'] == 'submit')
            trigger_error("attempt to set empty value", E_USER_ERROR);
        $this->fieldValue = $value;
        $this->possibleValues = array ($value);
        $dval = implode(' ', $this->possibleValues);
        $this->newValue = true;
//        echo "<br/>newValue: $this->row,$this->column = $this->fieldValue, pvals: $dval";
    }

    function getValue() {
        return $this->fieldValue;
    }
    
    function toString() {
        $dval = implode(' ', $this->possibleValues);
        return "field: $this->row, $this->column - value: $dval";
    }

}
?>