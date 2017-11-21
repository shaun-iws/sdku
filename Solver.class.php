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
 * Mike Diplock - added wing-check, chain-check, DLX solution, Feb. 2009
 * * <contributor2> - <description of contribution>
 *     ...
 *********************************************************************/
require "NumberField.class.php";
require "DLX.class.php";

class Solver {
    public $nfields = array ();
    protected $csvtext;
    protected $gnumber;
    protected $celllist = array();
    protected $maxcombos = array();
    protected $DLX;
    
    // create the 9x9 grid of number fields
    public function __construct() {
        for ($i = 1; $i <= 81; $i ++) {
            if (isset($_POST["f$i"])) $pstring = trim($_POST["f$i"]); else $pstring="";
            $this->nfields[$i] = new NumberField($i, $pstring);
	    $this->celllist['row'][$this->nfields[$i]->row][] = $i; // row list
	    $this->celllist['column'][$this->nfields[$i]->column][] = $i; // column list
	    $this->celllist['block'][$this->nfields[$i]->block][] = $i; // block list
        }
	for ($i=0; $i<=9; $i++)
	    for ($j=0; $j<=9; $j++)
		$this->maxcombos[$i][$j] = $this->getMaxNumberOfCombinations($i,$j);
	$this->DLX = new DLX();
    }

    protected function getMaxNumberOfCombinations($slots,$tokens){
	// Implementation of n choose r
	if ($slots < $tokens) return 0;
	if (($tokens == 0) || ($tokens == $slots)) return 1;
	$result = 1; $denom = 1;
	for ($i=$tokens+1; $i<=$slots; $i++) $result *= $i;
	for ($i=1; $i<=$slots-$tokens; $i++) $denom *= $i;
	$result = floor($result/$denom+0.5);
	return $result;
    }
    
    public function setUndoValues($us) {
        $this->emptyFields();
        foreach ($us as $seq => $pstring) {
            $pstring = trim($pstring);
            if ($pstring != '') {
                $field = $this->nfields[$seq];
                $pvals = explode(' ', $pstring);
                $cnt = count($pvals);
		$field->setValue('');
                if ($cnt <= 2){
		    if ($pvals[$cnt-1] != 0) $field->setValue($pvals[$cnt-1]); 
		}
                $field->possibleValues = array_slice($pvals,0,$cnt-1);
            }
        }
    }

    public function getUndoValues() {
	foreach($this->nfields as $field) {
            $seq = $field->seqno;
	    if ($field->hasValue()) $fv = $field->fieldValue; else $fv = 0;
            $us[$seq] = implode(' ', $field->possibleValues).' '.$fv;
        }
        $_SESSION['undostack'][] = $us;
    }
    
    // clear all the values in the grid
    public function clearFields() {
        foreach ($this->nfields as $field) {
            $field->setValue('');
            $field->possibleValues = range(1, 9);
        }
    }

    /* show blank fields */
    public function emptyFields() {
        foreach ($this->nfields as $field) {
            $field->setValue('');
        }
    }

    // go back to initial state
    public function resetFields() {
        if (count($_SESSION['undostack'])>0){
            $this->setUndoValues($_SESSION['undostack'][0]);
            unset($_SESSION['undostack']);
        }
        else echo "<br/>no reset data available.";
    }

    // show blank fields
    public function undo() {
        if (count($_SESSION['undostack'])>1){
            $last = array_pop($_SESSION['undostack']);
            $last = array_pop($_SESSION['undostack']);
            $this->setUndoValues($last);
        }
        else {
            //echo "<br/>nothing to undo!";
            $this->resetFields();
        }
    }

    // Check the possible values for all fields
    public function checkValues() {
        $this->checkFieldSet(true);
    }
    public function checkValuesOnly(){
        $this->checkFieldSet(false);
    }

    // Check the possible values for a set of fields
    public function checkFieldSet($fillin = true) {
	if ($fillin) $this->fillinCells();
	foreach ($this->nfields as $field) {
	    if ($field->hasValue()){
		$fieldList = $this->getAllFields($field);
		$this->diffAllValues($fieldList,array($field->fieldValue));
	    }
        }
    }
    
    protected function fillinCells(){
	foreach($this->nfields as $field){
	    if (count($field->possibleValues) == 1 && !$field->hasValue()) {
                $field->setValue($field->possibleValues[0]);
            }
	}
    }
    
    public function checkUnique($type) {
        for ($i = 1; $i < 10; $i ++) {
            $occurVals = array();
            foreach ($this->celllist[$type][$i] as $fieldno) {
		$field = $this->nfields[$fieldno];
		if (!$field->hasValue()){
		    foreach ($field->possibleValues as $j) $occurVals[$j][] = $fieldno;
                }
            }
            // is there a unique one?
	    for ($j = 1; $j < 10; $j ++) {
		if (count($occurVals[$j]) == 1) {
		    $field = $this->nfields[$occurVals[$j][0]];
                    $field->possibleValues = array($j);
		    // remove this possibility from any cell that can see this one
		    $this->diffAllValues($this->getAllFields($field),array($j));
                }
            }
        }
    }

    public function checkDouble() {
        for ($i = 1; $i < 10; $i ++) {
            $occurVals = array ();
            foreach ($this->celllist['block'][$i] as $fieldno) { // block
		$field = $this->nfields[$fieldno];
                if (!$field->hasValue()){
		    foreach ($field->possibleValues as $j) $occurVals[$j][] = $fieldno;
                }
            }
	    // If all occurrences of possible value occurr in a row or a column of a block then
	    // remove that possibility from other blocks
	    for ($j = 1; $j < 10; $j ++) {
		if (count($occurVals[$j]) > 1) {
		    $field = $this->nfields[$occurVals[$j][0]];
		    $fieldList = array_diff($occurVals[$j],$this->celllist['row'][$field->row]);
		    // Check if all cells in the same row
		    if (count($fieldList) == 0){
			// find the fields in the row, but not in the block
			//echo "found row ".$field->row." to eliminate $j<br>";
			$rowFields = array_diff($this->celllist['row'][$field->row],$this->celllist['block'][$i]);
			$this->diffAllValues($rowFields,array($j));
		    }
		    $fieldList = array_diff($occurVals[$j],$this->celllist['column'][$field->column]);
		    // check if all cells are in the same column
		    if (count($fieldList) == 0){
			// find the fields in the row, but not in the block
			//echo "found column ".$field->column." to eliminate $j<br>";
			$colFields = array_diff($this->celllist['column'][$field->column],$this->celllist['block'][$i]);
			$this->diffAllValues($colFields,array($j));
		    }
                }
            }
        }
    }

    protected function diffAllValues($fields, $values) {
	// if the field has a value, this value will be the only one in possibleValues
        foreach ($fields as $fieldno) {
	    $rfield = $this->nfields[$fieldno];
            if (!$rfield->hasValue()) {
                $rfield->possibleValues = array_values(array_diff($rfield->possibleValues, $values));
            }
        }
    }

    public function checkGrouping() {
	$this->checkGroupingByNumber(2);
	$this->checkGroupingByNumber(3);
	$this->checkGroupingByNumber(4);
	$this->checkGroupingByNumber(5);
    }

    protected function checkGroupingByNumber($gn){
    	$this->gnumber = $gn;
        $this->checkGroupingByType('row'); // row
	$this->checkGroupingByType('column'); // column
        $this->checkGroupingByType('block'); // block
    }

    protected function checkGroupingByType($type) {
        for ($i = 1; $i < 10; $i ++) {
	    // get array of fields which are not yet filled
	    $fieldList = array();
	    foreach ($this->celllist[$type][$i] as $fieldno) {
		$field = $this->nfields[$fieldno];
		if (count($field->possibleValues) > 1) $fieldList[] = $field->seqno;
	    }
	    if (count($fieldList) > $this->gnumber) {
		// if enough empty fields then iterate through all possible combinations
		$this->getAllCombinations($fieldList, $this->gnumber);
	    }
        }
    }
    
    protected function getAllCombinations($fieldList, $gnum){
	for ($i=0; $i<$this->maxcombos[count($fieldList)][$gnum]; $i++){
	    // get the cells to use for this iteration
	    $groupedCells = $this->getCombination($fieldList,$gnum,$i);
	    // get a list of possible values for this group
	    $possibleValues = array();
	    foreach ($groupedCells as $fieldno){
		$field = $this->nfields[$fieldno];
		// echo $field->seqno." ";
		foreach ($field->possibleValues as $value){
  		    if (!in_array($value, $possibleValues)){
			$possibleValues[] = $value;
		    }
		}
	    }
	    // echo "<br>";
	    // If the number of possible values is smaller than all possible values then we can eliminate
	    if ((count($possibleValues) <= $this->gnumber)){
		// echo "found group for poss = ".implode(' ',$possibleValues)."<br>";
		$fieldChangeList = array_diff($fieldList,$groupedCells);
		$this->diffAllValues($fieldChangeList,$possibleValues);
	    }
	}
    }
    
    protected function getCombination($fieldList, $gnum, $ind){
	// Implemenation of combinadic to find the nth combination of choosing 'gnum' from 'slots'
	$slotarr = array();
	for ($slot=1; $slot <= count($fieldList); $slot++){
	    if ($gnum == 0) break;
	    $threshold = $this->maxcombos[count($fieldList)-$slot][$gnum-1];
	    if ($ind < $threshold){
		$slotarr[] = $fieldList[$slot-1];
		$gnum--;
	    }else{
		$ind -= $threshold;
	    }
	}
	return $slotarr;
    }
    
    public function checkWings(){
	$this->checkWingsByNumber(2);
	$this->checkWingsByNumber(3);
	$this->checkWingsByNumber(4);
	//$this->checkWingsByNumber(5);
    }

    protected function checkWingsByNumber($gn){
	$this->gnumber = $gn;
	$this->checkWingsByType('row'); // row
	$this->checkWingsByType('column'); // column
	if ($gn == 2) $this->checkWingsByType('block'); // block
    }
    
    protected function checkWingsByType($type){
	// build a bitmask for every row, col or block for every value which still has possible entries
	if ($type == 'row'){$otype[0] = 'column'; $otype[1] = 'block';}
	else if ($type == 'column'){$otype[0] = 'row'; $otype[1] = 'block';}
	else if ($type == 'block'){$otype[0] = 'row'; $otype[1] = 'column';}
	for ($i = 1; $i < 10; $i ++) {
	    for ($j=0; $j<=9; $j++){$mask[0][$i][$j] = 0; $mask[1][$i][$j] = 0;}
	    foreach ($this->celllist[$type][$i] as $fieldno){
		$field = $this->nfields[$fieldno];
		for ($j=1; $j<=9; $j++){
		    if (in_array($j,$field->possibleValues)){
			$mask[0][$i][$j] |= 1 << ($field->$otype[0]-1); 
			$mask[1][$i][$j] |= 1 << ($field->$otype[1]-1);
		    }
		}
	    }
        }
	for ($l=0; $l<2; $l++){
	    if ($l>0 && $this->gnumber > 2) break; // only use 'block' on order 2 wings
	    for ($j=1; $j<=9; $j++){
		// Find which cells that only have a gnumber of candidates
		$possCells = array();
		for ($i=1; $i<=9; $i++){
		    if ($this->bitcount($mask[$l][$i][$j]) <= $this->gnumber && $this->bitcount($mask[$l][$i][$j]) >= 2){
			$possCells[] = $i;
		    }
		}
		// check all possible combinations to find how many total bits are set for a group
		// echo "count = ".count($possCells)." g = ".$this->gnumber."<br>";
		for ($k=0; $k<$this->maxcombos[count($possCells)][$this->gnumber]; $k++){
		    $groupedCells = $this->getCombination($possCells,$this->gnumber,$k);
		    $result = 0;
		    foreach ($groupedCells as $i) 
			$result = $result | $mask[$l][$i][$j];
		    if ($this->bitcount($result) == $this->gnumber){
			// structure has been found so eliminate unwanted possibilites
			$this->eliminateWing($j,$type,$otype[$l],$result,$groupedCells);
		    }
		}
	    }
	}
    }
    
    protected function eliminateWing($val,$type,$otype,$result,$cells){
	//echo "found x-wing for value $val, type '$type'-'$otype', order ".$this->gnumber.", with bitmap $result<br>";
	$fieldList = array();
	// get array of all cells in the structure
	foreach ($cells as $ind){
	    foreach ($this->celllist[$type][$ind] as $fieldno){
		$field = $this->nfields[$fieldno]; 
		$bitval = 1 << ($field->$otype-1);
		if ($bitval & $result) $fieldList[] = $field->seqno;
	    }
	}
	//foreach ($iFields as $field) echo $field->seqno." "; echo "<br>";
	// get array of cells of opposite type that intersect with structure, ignoring cells in structure
	$bits = $this->getbits($result);
	foreach ($bits as $bit){
	    $dFieldList = array_diff($this->celllist[$otype][$bit],$fieldList);
	    $this->diffAllValues($dFieldList,array($val));
	}
    }

    public function checkChains(){
	$this->checkSingleChains();
	$this->checkXYChains();
    }

    protected function checkSingleChains(){
    	$cpairs = array();
	foreach($this->nfields as $field){
	    if (!$field->hasValue()){
		for ($i=1; $i<=9; $i++){ // all values
		    if (in_array($i,$field->possibleValues)){
			$cpairs[$i][0][$field->row][] = $field->seqno;
			$cpairs[$i][1][$field->column][] = $field->seqno;
			$cpairs[$i][2][$field->block][] = $field->seqno;
		    }
		}
	    }
	}
	for ($i=1; $i<=9; $i++){
	    for ($j=1; $j<=9; $j++){
		for ($k=0; $k<3; $k++){
		    if (count($cpairs[$i][$k][$j]) == 2){
			$lnkcnt = 1;
			$efield = $cpairs[$i][$k][$j];
			$colour = array();
			$colour[0][] = $efield[0]; $colour[1][] = $efield[1];
			$efieldtype = array($k,$k);
			$colourtype = array(0,1);
			$extendchain = true;
			//echo "value $i - link ".$efield[0]." to ".$efield[1]." type $k<br>";
			while ($extendchain && $lnkcnt<20){
			    $extendchain = false;
			    for ($n=0; $n<2; $n++){
				$field = $this->nfields[$efield[$n]];
				for ($m=0; $m<3; $m++){
				    if ($m != $efieldtype[$n]){
					if ($m == 0) $offset = $field->row;
					elseif ($m == 1) $offset = $field->column;
					elseif ($m == 2) $offset = $field->block;
					if (count($cpairs[$i][$m][$offset]) == 2){
					    if ($cpairs[$i][$m][$offset][0] == $efield[$n])
						$newend = $cpairs[$i][$m][$offset][1]; 
					    else
						$newend = $cpairs[$i][$m][$offset][0];
					    if (!in_array($newend,$colour[0]) && !in_array($newend,$colour[1])){
						$efield[$n] = $newend;
						$extendchain = true;
						$lnkcnt++;
						$efieldtype[$n] = $m;
						$colourtype[$n] = 1-$colourtype[$n];
						$field = $this->nfields[$newend];
						$clash = false;
						foreach($colour[$colourtype[$n]] as $colno){
						    $colfield = $this->nfields[$colno];
						    if ($field->row == $colfield->row){$clash = true; break;}
						    if ($field->column == $colfield->column){$clash = true; break;}
						    if ($field->block == $colfield->block){$clash = true; break;}
						}
						if ($clash){
						    // if cell with same colour is found then this cannot be the correct set so set the other list
						    foreach($colour[1-$colourtype[$n]] as $colno){
							$colfield = $this->nfields[$colno];
							$colfield->possibleValues = array($i);
						    }
						}
						$colour[$colourtype[$n]][] = $newend;
						//echo "value $i - $lnkcnt extend $n to ".$efield[$n]." type ".$efieldtype[$n]."<br>";
						if (($lnkcnt & 1) && $lnkcnt != 1){
						    // at odd links in the chain elimante possiblities from any cell that can see both ends
						    $this->elimnateSeeBoth($efield[0],$efield[1],$i);	
						}
						$m = 3; $n = 2;
					    }
					}
				    }
				}
			    }
			}
		    }
		}
	    }
	}
    }

    protected function checkXYChains(){
	$dpairs = array();
	for ($i=0; $i<=9; $i++) $pvpairs[$i] = array();
	// find all cells with just two possibilities
	foreach ($this->nfields as $field){
	    if (count($field->possibleValues) == 2){
		$dpairs[] = $field->seqno;
		foreach ($field->possibleValues as $pv) $pvpairs[$pv][] = $field->seqno;
	    }
	}
	// go through every cell as the possible start of a chain using either value
	foreach($dpairs as $fieldno){
	    $field = $this->nfields[$fieldno];
	    foreach ($field->possibleValues as $pv){
		$chaincells = array();
		$lnkcnt = 0;
		$startpv = reset(array_diff($field->possibleValues,array($pv)));
		$this->walkThroughChain($fieldno,$lnkcnt,$pvpairs,$chaincells,$startpv,$pv,$fieldno);
	    }
	}
    }
    
    protected function walkThroughChain($thislink,$lnkcnt,&$pvpairs,$chaincells,$startpv,$pv,$fieldno){
	// find cells that have a matched pair
	$chaincells[] = $thislink;
	$fieldList = array_diff($this->getAllFields($this->nfields[$thislink]),$chaincells);
	$possCells = array_intersect($fieldList,$pvpairs[$pv]);
	if (count($possCells) > 0){
	    $lnkcnt++;
	    if ($lnkcnt > 81) return;
	    // if found then use each one in turn as the next branch of the chain
	    foreach ($possCells as $nextlink){
		$nextfield = $this->nfields[$nextlink];
		$nextpv = reset(array_diff($nextfield->possibleValues,array($pv))); // other end of chain
		//echo "length $lnkcnt from $fieldno to $nextlink new end $nextpv<br>";
		if ($lnkcnt > 1 && $nextpv == $startpv){
		    //echo "found chain length $lnkcnt from $fieldno to ".$nextfield->seqno." for value $startpv<br>";
		    // If ends of chain match then eliminate any cell that can see both ends and is not in chain
		    $section1 = $this->getAllFields($this->nfields[$fieldno]);
		    $section2 = $this->getAllFields($nextfield);
		    $eliminate = array_diff(array_intersect($section1,$section2),$chaincells);
		    $this->diffAllValues($eliminate,array($startpv));
		}
		$this->walkThroughChain($nextlink,$lnkcnt,$pvpairs,$chaincells,$startpv,$nextpv,$fieldno);
	    }
	}
    }
    
    public function solveDLX(){
	$grid = '';
	foreach ($this->nfields as $field){
	    if ($field->hasValue())
		$grid .= $field->fieldValue;
	    else
		$grid .= '0';
	}
	$sols = $this->DLX->setupDLX($grid,true);
	if (count($sols) == 1){
	    $index = 0;
	    foreach ($this->nfields as $field){
		if (!$field->hasValue()){
		    $val = substr($sols[0],$index,1);
		    $field->setValue($val);
		}
		$index++;
	    }
	}elseif (count($sols) == 0) echo "No Solution!<br>";
	else echo "More than one solution!<br>";
    }
    
    protected function elimnateSeeBoth($fldno1,$fldno2,$val){
	$section1 = $this->getAllFields($this->nfields[$fldno1]);
	$section2 = $this->getAllFields($this->nfields[$fldno2]);
	//print_r(array_intersect($section1,$section2)); echo "<br>";
	$this->diffAllValues(array_intersect($section1,$section2),array($val));
    }
    
    protected function bitcount($val){
	$bitval = 1; $bcnt = 0;
	while ($bitval < $val){
	    if ($val & $bitval) $bcnt++;
	    $bitval = $bitval << 1;
	}
	return $bcnt;
    }
    
    protected function getAllFields($field){
	$fieldList = array_unique(array_merge($this->celllist['row'][$field->row],$this->celllist['column'][$field->column],$this->celllist['block'][$field->block]));
	$fieldList = array_diff($fieldList,array($field->seqno));
	return $fieldList;
    }
    
    protected function getbits($val){
	$bitval = 1; $n = 1;
	$bitarr = array();
	while ($bitval < $val){
	    if ($val & $bitval) $bitarr[] = $n;
	    $bitval = $bitval << 1;
	    $n++;
	}
	return $bitarr;
    }

}

?>
