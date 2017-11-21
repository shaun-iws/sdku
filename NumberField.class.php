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
    public $fieldValue;
    public $possibleValues;
    public $block;
    public $column;
    public $row;
    public $seqno;
    protected $newValue = false;

    public function __construct($value, $pstring) {
        $this->seqno = $value;
        $this->column = (($value -1) % 9) + 1;
        $this->row = (int) ceil($value / 9);
        $blockrow = (int) ceil($this->row / 3);
        $this->block = (($blockrow -1) * 3) + (int) ceil($this->column / 3);
        $pvals = explode(' ', $pstring);
        $cnt = count($pvals);
	$this->fieldValue = '';
        if (count($pvals) == 1) {
            $this->fieldValue = $pvals[0];
            $this->possibleValues = array($pvals[0]);
	} else if (count($pvals) == 2) {
	    if ($pvals[1] != 0) $this->fieldValue = $pvals[1];
	    $this->possibleValues = array($pvals[0]);
	} else if (count($pvals) > 2) {
            $this->possibleValues = array_slice($pvals,0,count($pvals)-1);
        } else {
            $this->possibleValues = range(1, 9);
        }
        //echo "<br/>constructing: $this->seqno - $pstring, count: $cnt";
	//echo " - fieldval: ", $this->fieldValue, " - posval: ", implode(' ', $this->possibleValues);
    }

    public function displayField() {
	$seqno = $this->seqno;
        if ($this->hasValue()){
            if ($this->newValue) $txttype = 'rtype'; else $txttype = 'ltype';
	    $fv = $this->fieldValue;
	}else{
            $txttype = 'stype';
	    $fv = 0;
	}  
	$dval = implode(' ', $this->possibleValues);
        if (trim($dval) == '') $txttype = 'ltype';  
        if ($txttype == 'stype') $divclass = 'scell'; else $divclass = 'ncell'; 
	$fld = "<td class='$txttype'><div class='$divclass' id='".$this->seqno."' onclick='fieldClick(this);'>" ;
        if (trim($dval) == '') { 
	    $fld .= "<input tabindex='$seqno' class='ltype' type='text' id='i$seqno' name='f$seqno' value='$dval'/>";
        } else {
	    $fld .= $dval."<input tabindex='$seqno' class='$txttype' type='hidden' id='i$seqno' name='f$seqno' value='$dval $fv'/>";
        }
        $fld .= "</div></td>\n";
        return $fld;
    }

    public function hasValue() {
        if (trim($this->fieldValue) != '')
            return TRUE;
        else
            return FALSE;
    }

    public function setValue($value) {
	if (isset($_POST['submit']))
            if (trim($value) == '' && $_POST['submit'] == 'submit')
            	throw new Exception('attempt to set empty value');
        $this->fieldValue = $value;
        $this->possibleValues = array($value);
        $this->newValue = true;
        //echo "<br/>newValue: $this->row,$this->column = $this->fieldValue";
    }

    public function getValue() {
        return $this->fieldValue;
    }
    
    public function toString() {
        $dval = implode(' ', $this->possibleValues);
        return "field: $this->row, $this->column - value: $dval";
    }

}
?>
