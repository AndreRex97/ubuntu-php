<?php

// Unused constant (to trigger DEAD_CODE set)
define('UNUSED_CONSTANT', 123);

// Unused function
function unused_helper()
{
    return true;
}

// Global threshold value
$ThresholdValue = 50;

// Legacy array and loose variable naming
$DataSet = array(
    array('score' => 45, 'name' => 'Alice'),
    array('score' => 82, 'name' => 'Bob'),
    array('score' => 67, 'name' => 'Charlie'),
    array('score' => 30, 'name' => 'Derek'),
);

function GenerateReport($input)
{
    $output = array();
    foreach ($input as $Row) {
        // nested if block
        if ($Row['score'] !== null) {
            if ($Row['score'] >= 50) {
                $status = 'pass';
            } else {
                $status = 'fail';
            }
        } else {
            $status = 'unknown';
        }

        // repeated logic (simplifiable)
        $output[] = array(
            'student' => $Row['name'],
            'score' => $Row['score'],
            'result' => $status
        );
    }

    return $output;
}

function PrintReport($data)
{
    foreach ($data as $entry) {
        echo $entry['student'] . ' - ' . strtoupper($entry['result']) . "\n";
    }
}

$report = GenerateReport($DataSet);
PrintReport($report);
