<?php
/**
 * Calculates the factorial of a given non-negative integer.
 *
 * The factorial of a number n (written as n!) is the product of all positive integers less than or equal to n.
 * For example, factorial(5) = 5 * 4 * 3 * 2 * 1 = 120.
 *
 * @param int $n The non-negative integer whose factorial is to be calculated.
 * @return int The factorial of the input number. Returns 1 if $n is 0.
 */
function factorial($n) {
    $result = 1;
    for ($i = 1; $i <= $n; $i++) {
        $result *= $i;
    }
    return $result;
}

echo factorial(5);
?>