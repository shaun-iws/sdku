<?php
// Class to implement DLX ("Dancing Links") solution for sudokus
// based on C# code by Lee A Neuse at http://www.humble-programmer.com/bb_sudoku.htm

class DLX {
    protected $boxsize = 3;
    protected $size = 9;
    protected $gridsize = 81;
    protected $constraints = 324; // 4 * gridsize
    protected $rows = 729; // size * gridsize

    protected $init = false;
    protected $iCols = array();
    protected $iRows = array();
    protected $col = array();
    protected $row = array();

    protected $iSolutions;
    protected $bColFlags = array();
    protected $iRowFlags = array();

    protected $inpgrid;
    protected $Solutions = array();
    
    public function __construct() {
	$this->init = true;
	$idx = 0;
	for ($iR=0; $iR < $this->size; ++$iR){
	    for ($iC=0; $iC < $this->size; ++$iC){
		for ($z=1; $z <= $this->size; ++$z){
		    $idx++;
		    $this->iCols[$idx] = 4;
		    $this->col[$idx][0] = ($iR * $this->size) + $iC + 1;
		    $this->col[$idx][1] = (($this->boxsize * floor($iR / $this->boxsize)) + floor($iC / $this->boxsize)) * $this->size + $this->gridsize + $z;
		    $this->col[$idx][2] = ($iR * $this->size) + (2 * $this->gridsize) + $z;
		    $this->col[$idx][3] = ($iC * $this->size) + (3 * $this->gridsize) + $z;
		}
	    }
	}
	for ($iC=1; $iC <= $this->constraints; ++$iC) $this->iRows[$iC] = 0;
	for ($iR=1; $iR <= $this->rows; ++$iR){
	    for ($iC=0; $iC < $this->iCols[$iR]; ++$iC){
		$idx = $this->col[$iR][$iC];
		$this->iRows[$idx]++;
		$this->row[$idx][$this->iRows[$idx]] = $iR;
	    }
	}
    }
    
    public function setupDLX($inp, $bFindAll){
	$this->iSolutions = 0;
	$this->inpgrid = $inp;
	for ($idx=0; $idx <= $this->constraints; ++$idx) $this->bColFlags[$idx] = false;
	for ($idx=0; $idx <= $this->rows; ++$idx) $this->iRowFlags[$idx] = 0;
	
	$index = 0; $iEmpty = 0;
	for ($iR=0; $iR < $this->size; ++$iR){
	    for ($iC=0; $iC < $this->size; ++$iC){
		// get grid from input string
		$iValue = substr($inp,$index++,1);
		//echo $iValue;
		if ($iValue >=1 && $iValue <= $this->size){
		    $iState = $iValue + ($iR * $this->gridsize) + ($iC * $this->size);
		    for ($j=0; $j<$this->iCols[$iState]; ++$j){
			$idx = $this->col[$iState][$j];
			if ($this->bColFlags[$idx]){return 0;}
			$this->bColFlags[$idx] = true;
			for ($k=1; $k <= $this->iRows[$idx]; ++$k){
			    $this->iRowFlags[$this->row[$idx][$k]]++;
			}
		    }
		}else $iEmpty++;
	    }
	}
	$this->solve($iEmpty, !$bFindAll);
	return $this->Solutions;
    }

    public function solve($iEmpty, $bQuick){
	if ($iEmpty > ($this->gridsize - 17)) return;

	$iSolved = 0; $iState = 0;
	for (;;){
	    switch ($iState){
		case 0:
		    $I[++$iSolved] = 0;
		    $iBest = $this->rows + 1;
		    for ($iC=1; $iC <= $this->constraints; ++$iC){
			if (!$this->bColFlags[$iC]){
			    $iMatch = 0;
			    for ($iR=1; $iR <= $this->iRows[$iC]; ++$iR){
				if ($this->iRowFlags[$this->row[$iC][$iR]] == 0) $iMatch ++;
			    }
			    if ($iMatch < $iBest){
				$iBest = $iMatch;
				$C[$iSolved] = $iC;
			    }
			}
		    }
		    $iState = ($iBest > 0 && $iBest <= $this->rows) ? 1 : 2;
		    break;
		    
		case 1:
		    $iC = $C[$iSolved];
		    $I[$iSolved]++;
		    if ($I[$iSolved] > $this->iRows[$iC]){
			$iState = 2;
			continue;
		    }
		    $iR = $this->row[$iC][$I[$iSolved]];
		    if ($this->iRowFlags[$iR] != 0){
			$iState = 1;
			continue;
		    }
		    if ($iSolved == $iEmpty){
			$this->iSolutions++;
			$index = 1;
			$grid = $this->inpgrid;
			foreach($C as $cc){
			    $v = $this->row[$cc][$I[$index]]-1;
			    $value = ($v % $this->size)+1;
			    $cellno = floor($v/$this->size);
			    $row = floor($cellno/$this->size)+1;
			    $col = ($cellno % $this->size)+1;
			    //echo "$v ($row,$col)=$value ";
			    $grid = substr_replace($grid,$value,($row-1)*$this->size+$col-1,1);
			    $index++;	    
			}
			$this->Solutions[] = $grid;
			if ($bQuick && $this->iSolutions > 1) return;
		    }
		    for ($j=0; $j<$this->iCols[$iR]; ++$j){
			$idx = $this->col[$iR][$j];
			$this->bColFlags[$idx] = true;
			for ($k=1; $k<=$this->iRows[$idx]; ++$k){
			    $this->iRowFlags[$this->row[$idx][$k]]++;
			}
		    }
		    $iState = 0;
		    break;

		case 2:
		    $iC = $C[--$iSolved];
		    $iR = $this->row[$iC][$I[$iSolved]];
		    for ($j=0; $j < $this->iCols[$iR]; ++$j){
			$idx = $this->col[$iR][$j];
			$this->bColFlags[$idx] = false;
			for ($k=1; $k <= $this->iRows[$idx]; ++$k){
			    $this->iRowFlags[$this->row[$idx][$k]]--;
			}
		    }
		    $iState = ($iSolved > 0) ? 1 : -1;
		    break;

		default:
		    return;			
	    }
	}
    }
}

?>
