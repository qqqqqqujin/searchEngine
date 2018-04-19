<?php
if(isset($_GET["input"])) {
            $input = $_GET["input"];            
        } 

$url = "http://localhost:8983/solr/mycore/suggest?q={$input}&wt=json";
$file = file_get_contents($url);
echo $file;
?>