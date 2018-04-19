<?php
ini_set('memory_limit', '3000M');
include 'SpellCorrector.php';
// make sure browsers see this page as utf-8 encoded HTML
header('Content-Type: text/html; charset=utf-8');
$limit = 10;
$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false; 
$results = false;

$csv = array_map('str_getcsv', file('USATodayMap.csv'));

$map = array();
foreach ($csv as $value) {
	$temp = "/Users/jinqu/Desktop/572/HW4/USAToday/USAToday/" . $value[0];	
	$map[$temp] = $value[1];
    
}

$new_query = "";    //represents whether the query is correct
//$query_name = "";
$echo_correct = false;
$echo_words = "";   //to echo the corrected query

//echo SpellCorrector::correct("iphome");

if ($query)
{
    // The Apache Solr Client library should be on the include path 
    // which is usually most easily accomplished by placing in the
    // same directory as this script ( . or current directory is a default 
    // php include path entry in the php.ini) 
    require_once('Apache/Solr/Service.php');

    // create a new solr service instance - host, port, and corename
    // path (all defaults in this example)
    $solr = new Apache_Solr_Service('localhost', 8983, '/solr/mycore/');
    // if magic quotes is enabled then stripslashes will be needed
    if (get_magic_quotes_gpc() == 1) {
        $query = stripslashes($query); 
    }
    
    // in production code you'll always want to use a try /catch for any
    // possible exceptions emitted by searching (i.e. connection
    // problems or a query parsing error)
    try
    {

        $additionalParameters = array('sort' => '');
        if (isset($_GET["pr"])) {
            $additionalParameters = array('sort' => 'pageRankFile desc');
        }
        
        //spell corrector: 
        $terms = explode(" ", $query);
        for ($i = 0; $i < sizeOf($terms); $i++) {
            $check = SpellCorrector::correct($terms[$i]);
            //echo $terms[$i];
            //echo $check;
            if ($new_query != "") {
                //it is not the first term
                $new_query = $new_query . "+" . trim($check);
            } else {
                $new_query = trim($check);
                //$query_name = $query_name." ".trim($check);
            }
        }
        $query_name = str_replace("+", " ", $new_query);
        //echo $new_query;
        //echo $query;
        if (strtolower($query) != strtolower($query_name)) {
            //the query spells is incorrect
            //echo $query_name;
            $echo_correct = true;
            $link = "http://localhost/index.php?q=$new_query";
            $echo_words = "Did you want to search: <a href='$link'>$query_name</a>";
        }
        $results = $solr->search($query, 0, $limit, $additionalParameters);
    }
    catch (Exception $e)
    {
        // in production you'd probably log or email this error to an admin
        // and then show a special message to the user but for this example
        // we're going to show the full exception
        die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
    } 
}
?> 

<html>
    <head>
        <title>Jin Qu's Search Engine</title>
        <script src="//code.jquery.com/jquery-1.9.1.js"></script>
        <script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
        <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">  
        <script>
            function auto() {
                var input = $('#q').val();  
                    $('#q').autocomplete({        
                        source: function( request, response ) {
                            $.ajax({                
                                url: "http://localhost/auto.php?input=" + input,
                                dataType: "json",
                                data: {term: request.term},
                                success: function(data) {
                                    var arr = data["suggest"]["suggest"][input]["suggestions"];
                                    response($.map(arr, function(item) {
                                        return {
                                            label:item["term"]                            
                                            };
                                    }));
                                }
                            });            
                        },
                        minLength: 1
                    })    
                }
        </script>
    </head>
    <body>
        <form accept-charset="utf-8" method="get">
            <label for="q">Search:</label>
            <input id="q" name="q" type="text" onkeyup="auto()" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>"/>
            <input type="checkbox" name="pr" <?php if(isset($_GET['pr'])) echo "checked='checked'"; ?>> PageRank
            <input type="submit" value="SEARCH!"/> 
        </form>
<?php
        
if ($echo_correct) {
    echo $echo_words;
}        
        
        
// display results
if ($results) 
{
    $total = (int) $results->response->numFound; 
    $start = min(1, $total);
    $end = min($limit, $total);
?>
    <div>Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total; ?>:</div>

    <ol> 
<?php
    // iterate result documents
    foreach ($results->response->docs as $doc)
    { 
?>
    <li>
        <table style="text-align: left">

            <tr>
                <th >
                    <a href="<?php echo($map[$doc->id]) ?>" target="_blank">
                        <?php echo htmlspecialchars($doc->title, ENT_NOQUOTES, 'utf-8'); ?>
                    </a>
                </th>
                
            </tr>

            <tr>
                <td style="color:grey">
                    <a href="<?php echo($map[$doc->id]) ?>" target="_blank">
                        <?php echo htmlspecialchars($map[$doc->id], ENT_NOQUOTES, 'utf-8'); ?>
                    </a>
                </td> 
                
            </tr> 
            <tr>
                <td>
                        <?php echo htmlspecialchars($doc->id, ENT_NOQUOTES, 'utf-8'); ?>
                </td>
                
            </tr> 
            <tr>
                <td><?php echo htmlspecialchars($doc->description, ENT_NOQUOTES, 'utf-8'); ?></td>
            </tr>
            <tr>
                <td>Snippet: 
                    <?php
                    $query_terms = explode(' ', $query);
                    $flag = false;
                    $doc_path = str_replace("/Desktop/572/HW4", "/Sites", $doc->id);
                    //echo $doc_path;
                    $text = strip_tags(file_get_contents($doc_path),'<script><style>');
                    $text = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $text); 
                    $text = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', "", $text); 
                    //echo $text."*********";
                    $text = str_replace("?", ".", $text);
                    $text = str_replace("!", ".", $text);
                    $arr = explode('.', $text);
                    //echo $arr;
                    $snippet_words = "";
                    $word_arr = explode(' ', $snippet_words);
                    $max_count = 0;
                    foreach ($arr as $key) {
                        
                        $found = 0;
                        //$current_snippet = "";
                        foreach ($query_terms as $term) {
                            foreach(explode(' ', $key) as $single_word) {
                                if (strtolower($single_word) == strtolower($term)) {
                                    $found += 1;
                                    $flag = true;
                                    if ($found == sizeof($query_terms)) {
                                        break;
                                    }
                                }
                            }
                        }
                        if ($found > $max_count) {
                            $max_count = $found;
                            $snippet_words = $key;
                        }
                    }
                    //echo count(explode(' ', $snippet_words));
                    //echo $snippet_words;
                    if ($flag == false) {
                        echo "No valid Snippets";
                    } else if (strlen($snippet_words) <= 160) {
                        echo $snippet_words;
                        echo ".";
                    } else {
                        //echo $snippet_words;
                        echo "...";
                        $first_term = "";
                        foreach($query_terms as $term) {
                            if (strpos(strtolower($snippet_words), strtolower($term)) != false) {
                                //echo "*****".$term."*****";
                                $first_term = $term;
                                break;
                            }
                        }
                        //echo $first_term;
                        $trim_words = stristr($snippet_words, $first_term);
                        //echo $trim_words;
                        //echo "trim: " . $trim_words;
                        //echo strlen($trim_words);
                        if(strlen(($trim_words)) > 160) {
                            $trim_words = substr($trim_words, 0, 160);
                            echo $trim_words . "...";
                        } else {
                            echo $trim_words . ".";
                        }
                        
                    }
                    
		          ?>
                </td>
            </tr>

        </table> 
    </li>
<?php 
    }
?> 
        
</ol>       
        
<?php 
}
?>
    
    </body> 
</html>
