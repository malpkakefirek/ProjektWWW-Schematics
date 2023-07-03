<h3>EXAMPLES: PHP TAGS</h3><br />

<!-- Here we can use HTML code and comments. -->
1. Sample line written in HTML.<br />

<?php
// Here we can use PHP code and comments.
print '2. Sample line written in PHP.<br />';
?>

3. Sample line written in HTML.<br />

<?php
$sample_variable = 1;
if ($sample_variable == 1) {
    print '4. Sample line written in PHP because condition "$sample_variable == 1" is true.<br />';
}
?>

5. Sample line written in HTML.<br />

<?php
if ($sample_variable == 1) {
    print '6. In this PHP block we can still use variable $sample_variable.<br />';
    ?>

    7. This line is written in HTML but is inside the "if" statement. Crazy!<br />

    <?php
    print '8. We are still inside the "if" statement :)<br />';
}

print '9. Now we are outside of the "if" statement.<br /><br /><br />';



print '<h3>EXAMPLES: ARRAY HANDLING IN PHP</h3><br />';

print 'An array indexed by numbers:<br />';

$array_indexed_numbers = ['a', 'b', 'c', 'd'];

foreach ($array_indexed_numbers as $key => $value) {
    print $key . ": " . $value . "<br />";
}

print "<br />---------------------------------<br /><br />";

print 'An associative array:<br />';

$array_associative = [
    'a' => 'b',
    'c' => 'd',
    'e' => 'f',
    'g' => 'h'
];

foreach ($array_associative as $key => $value) {
    print $key . ": " . $value . "<br />";
}

print "<br />---------------------------------<br /><br />";

print 'An empty array:<br />';

$array_empty = [];
print_r($array_empty); // foreach would not display anything, so you can use
                       // the print_r function to display the contents
                       // of the array (check it in PHP doc)

print "<br /><br />---------------------------------<br /><br />";

print 'Manually add, delete and edit array elements:<br />';

$arr = ['a' => 'first test', 'b' => 'second test'];

$arr['c'] = 'third test';

$arr['a'] = 'first test changed';

unset($arr['b']); // unset removes a variable, in this case an array element

print_r($arr);

print "<br /><br />---------------------------------<br /><br />";

print 'Displaying specific array elements:<br />';

$arr1 = ['a', 'b', 'c', 'd'];
$arr2 = ['a' => 'first test', 'b' => 'second test'];

print $arr1[0] . "<br />"; // 'a'
print $arr2['b'] . "<br />"; // 'second test'

print "<br />---------------------------------<br /><br />";

print 'Editing array elements in the foreach loop:<br /><br />';

$arr = ['a' => 'first test', 'b' => 'second test'];

foreach ($arr as $key => $value) {
    $arr[$key] = $value . ' changed'; // we add the word "changed" to each element,
                                      // we do not edit $value variable itself because it is temporary within the loop
                                      // and changing it does not affect the array
}

print_r($arr);
