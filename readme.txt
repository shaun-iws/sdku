1. To use the sudoku solver, just unpack the package to a directory of your choice.
2. Place the examples in a local directory on your client.
3. !!! Create a subdirectory of the directory where you put the Sudoku Solver classes,
call it temp, and make sure you can move uploaded files (the Sudoku examples) 
to this directory, by making it writable. 
4. Start the solver by executing the sudoku.php script.

A PHP4 version can be found in the sudoku4 subdirectory (note point 3 also)

===========================================================================================
Summary of changes made by Mike Diplock - Feb. 2009
 
I have attached the files I changed to implement the x-wing solution ( http://www.palmsudoku.com/pages/techniques-8.php ) and I have also attached three puzzles that require the x-wing solution.
 
I found that the array_diff function acting on objects does not work in php 5.2.8 due to changes in _to_string() so I have modified getRCB to actually ignore certain fields when it builds the array rather than doing the diff afterwards.
 
I have completely rewritten the group check as I couldn't work out how the old method worked and it seemed overly complicated. I have implemented it here using a combinadic algorithm which is more efficent and was also required for the x-wing solution.

===========================================================================================
Ton Meuleman - added the checkGrouping solution, March 2007

===========================================================================================
original author: Ghica van Emde Boas, Sept. 2005
 