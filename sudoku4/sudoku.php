<?php
session_start();
if (isset($_SESSION['clicks'])) $_SESSION['clicks']++;
else $_SESSION['clicks'] = 1;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" 
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head><title>Sudoku Solver</title>
<!--
 * Created on 31-Aug-2005
 **********************************************************************
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
 *********************************************************************
-->
  <meta name="keywords" content="sudoku solver php">
  <link rel="stylesheet" type="text/css" href="sudoku.css" />
<script language="JavaScript">
<!--
function launchhelp() {
    window.open('sudoku-help.html', 'Sudoku', 'width=700, height=700, left=200, screenX=200, top=200, screenY=200, resizable=yes, scrollbars=yes');
}
function fieldClick(field) {
var fname='f'+field.id;
var iname='i'+field.id;
var oldElem = document.getElementById(iname);
var oldValue = oldElem.value;
//var oldClass = oldElem.class;
var divElem = document.createElement('div');
divElem.setAttribute('class', 'scell');

    if (oldElem.type == 'hidden') {
//        var newElem = oldElem.cloneNode(true);
        var newElem = document.createElement('input');
        newElem.setAttribute('name', fname);
        newElem.setAttribute('class', 'stype');
        newElem.setAttribute('type', 'text');
        newElem.setAttribute('value', oldValue);
        oldElem.setAttribute('name', 'r'+fname);
        divElem.appendChild(newElem);
        var rm = field.parentNode.replaceChild(divElem, field);
    }
}
//-->
</script>
</head>
<body>
<?php
require "Sudoku.class.php";

$sud = &new Sudoku();
$sud->checkAction();
$sud->display();
//echo "count undostack: ", count($_SESSION['undostack']);
//echo "<pre>";
//print_r($_SESSION['undostack']);
//echo "</pre>";
?>
</body>
</html>