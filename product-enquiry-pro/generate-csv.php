<?php

$data = json_decode($_POST['data']);

$head = array();

$hd = array();

// $head=implode(',',$hd);
if (sizeof($data) > 0) {
    $date = new DateTime();

    $ts = $date->format('Y-m-d-G-i-s');

    $filename = "report-$ts.csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment;filename='.$filename);

    $fp = fopen('php://output', 'w');
    $keys = array();
    
    foreach ($data[0] as $k => $v) {
        array_push($keys, $k);
    }
    foreach ($data as $v) {
    }

    fputcsv($fp, $keys);
    foreach ($data as $k => $v) {
        $values = array();
        foreach ($v as $m => $n) {
            array_push($values, $n);
        }
        fputcsv($fp, $values);
    }

    fclose($fp);
}

return true;
