<?php

$action = $_GET["a"] ?? '';
$currentData = json_decode(file_get_contents('data/current.json'));

if (isset($action)) {
  switch ($action) {
    case 'set_eta':
      $eta = $_GET['eta'];
      $currentData[1]->eta = $eta;
      file_put_contents('data/current.json', json_encode($currentData));
      break;
    case 'init':
      $origin = $_GET['o'];
      $destination = $_GET['d'];
      file_put_contents('data/current.json', json_encode([$origin, $destination]));
  }
}