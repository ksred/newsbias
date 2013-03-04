<?php

require_once('magpierss/rss_fetch.inc');
require_once('functions.php');

// Get Data  
$url = strip_tags($_POST['url']);
//set up caching
define('MAGPIE_CACHE_DIR', 'magpierss/cache');
define('MAGPIE_CACHE_ON', 1);
define('MAGPIE_CACHE_AGE', 1800); //1800 seconds

echo "URL = ".$url."<br />";
//get the items out of the rss feed
$rss = fetch_rss( $url );
//var_dump($rss);
//number of keywords to keep in topic
$noOfTopicKeys = 10;

//open the db
$dbconnect = db_connect();
$stop = false;
if (($rss) && (!$stop)) { //this is for testing FIX
        echo 'yay rss<br />';
	echo '<br />';
        
        $source=$rss->channel['title'];
        $source_link = $rss->channel['link'];
        echo "SOURCE IS $source";
        //var_dump($rss);
        
        foreach ($rss->items as $item) {
            $title=$item['title'];
	    
            //$content1=$item['encoded']; //these are causing duplication in the database
	    $content2=$item['description'];
	    //$content3=$item['atom_content'];
	    //$content4=$item['summary'];
	    //if (is_null($content1)) { $content1 = ' '; }
	    //if (is_null($content2)) { $content2 = ' '; }
	    //if (is_null($content3)) { $content3 = ' '; }
	    //if (is_null($content4)) { $content4 = ' '; }
	    //$content= $content1." ".$content2." ".$content3." ".$content4;
	    $content = $content2;
	    //echo "#!# CONTENT : $content";
	    $content = str_replace(chr(39), '', $content);
	    $content = str_replace('-', '', $content);
	    $content = str_replace('.', '', $content);
	    $content = str_replace('"', '', $content);
	    $title = str_replace(chr(39), '', $title);
	    $title = str_replace('-', '', $title);
	    $title = str_replace('.', '', $title);
	    $title = str_replace('"', '', $title);
	    //$content = str_replace(chr(39), '', $content); # Also replace double quote and fullstop
            $date=$item['date'];
            $link=$item['link'];
            
            //insert the keys into the db - might be worthwhile to sort them acc to pop here
	    $title = strtolower($title);
	    $content = strtolower($content);
	    $content_test = $content;
	    $title = clean_HTML($title);
	    $content = clean_HTML($content);
	    $content_test = clean_HTML($content);
	    echo "###";
	    var_dump($content_test);
	    echo "<br />";
	    
	    //only input new rss items, exit on items already in the db
	    $newHash = md5($title);	    
//	    $hashQ = "select count(*) as count from article where `hash` = '$newHash'";
//            $hashArr = mysql_query($hashQ, $dbconnect);
//            $hashArr = mysql_fetch_assoc($hashArr);
	    
	    $hashArr = sql_query("select count(*) as count from article where `hash` = '$newHash'");
	    
	    echo "<br />title $title // compare query $hashQ<br />";
	    if ($hashArr['count'] > 0) { break; }
	    
	    $titleArr = explode(' ', $title);
	    $contentArr = explode(' ', $content);
	    $allContent = array_merge($titleArr, $contentArr);
	    $nounArray = get_nouns($allContent);
	    //right now not finding any common keys?
	    $nounKeys = get_keys($nounArray);
	    
	    ////Do database inserts
	    //insert source id or find source id
	    $que3 = '';
            //$sourceQuery = "select max(id) as id, max(`source_id`) as source_id from source";
            //$sourceIdArr = mysql_query($sourceQuery, $dbconnect);
            //$sourceIdArr = mysql_fetch_assoc($sourceIdArr);
	    
	    $sourceIdArr = sql_query("select max(id) as id, max(`source_id`) as source_id from source");
	    
            $sourceid = $sourceIdArr['id'] + 1;
            $sourceid2 = $sourceIdArr['source_id'] + 1;
	    
//	    $sourceNameQuery = "select * from source where `source_name` = '$source'";
//            $sourceNameIdArr = mysql_query($sourceNameQuery, $dbconnect);
//            $sourceNameIdArr = mysql_fetch_assoc($sourceNameIdArr);
            
	    $sourceNameIdArr = sql_query("select * from source where `source_name` = '$source'");
	    
	    $sourceName = $sourceNameIdArr['source_name'];
	    $oldSourceID = 0;
	    if ($sourceNameIdArr == false) {
		$que3="insert into `source` select $sourceid, $sourceid2, '$source', '$url', '$source_link'";
		$que9 = "insert into `source_trust` select $sourceid, $sourceid2, 5";
	    } else {
		$oldSourceID = $sourceNameIdArr['source_id'];
	    }
	    
	    //insert into article
            //$idQuery = "select max(id) as id, max(`article_id`) as article_id from article";
            //$idarr = mysql_query($idQuery, $dbconnect);
            //$idarr = mysql_fetch_assoc($idarr);
	    
	    $idarr = sql_query("select max(id) as id, max(`article_id`) as article_id from article");
	    
            $id = $idarr['id'] + 1;
            $articleid = $idarr['article_id'] + 1;
	    $hash = md5($title);
            var_dump($id);
	    
//	    $sourceAQuery = "select max(s.id) as maxsid, max(sa.id) as maxsaid from source_article sa, source s";
//            $sourceAArr = mysql_query($sourceAQuery, $dbconnect);
//            $sourceAArr = mysql_fetch_assoc($sourceAArr);
            
	    $sourceAArr  = sql_query("select max(s.id) as maxsid, max(sa.id) as maxsaid from source_article sa, source s");
	    
	    $sourceAID = $sourceAArr['maxsaid'] + 1;
	    $sourceNewID = $sourceAArr['maxsid'] + 1;
	    if ($oldSourceID !== 0) {
		$que1="insert into `article` select $id, $articleid, $oldSourceID, '$link', '$hash'";
	    } else {
		$que1="insert into `article` select $id, $articleid, $sourceNewID, '$link', '$hash'";
	    }
	    //make sure that articles dont get inserted twice, using article name
	    
	    //insert into source article
	    if ($oldSourceID !== 0) {
		$que6="insert into `source_article` select $sourceAID, $oldSourceID, $articleid";
	    } else {
		$que6="insert into `source_article` select $sourceAID, $sourceNewID, $articleid";
	    }
	    
            //insert the article meta
            //$idQuery = "select max(id) as id from article_meta";
            //$idarr = mysql_query($idQuery, $dbconnect);
            //$idarr = mysql_fetch_assoc($idarr);
            
	    $idarr = sql_query("select max(id) as id from article_meta");
	    
	    $id = $idarr['id'] + 1;
            $que2="insert into `article_meta` values ($id, $articleid, '$title', '$content')";
            
            
	    //insert into topic article
	    //insert keys for article
//	    $keyQuery = "select max(id) as id from article_keys";
//            $keyarr = mysql_query($keyQuery, $dbconnect);
//            $keyarr = mysql_fetch_assoc($keyarr);
            
	    $keyarr = sql_query("select max(id) as id from article_keys");
	    
	    $keyid = $keyarr['id'] + 1;
	    $titleArr = explode(' ', $title);
	    $titleKeys = get_nouns($titleArr);
	    $titleKeys = get_keys($titleKeys);
	    $titleKeysStr = '';
	    foreach ($titleKeys as $tKey) {
		$titleKeysStr = $titleKeysStr.','.$tKey;
	    }
	    echo "<br />TESTING FIX titlekeys = $titleKeysStr<br />";
	    $titleKeysStr = substr($titleKeysStr, 1);
	    $contentArr1 = explode(' ', $content);
	    $contentKeys = get_nouns($contentArr1);
	    $contentKeys = get_keys($contentKeys);
	    $contentKeysStr = '';
	    foreach ($contentKeys as $tKey) {
		$contentKeysStr = $contentKeysStr.','.$tKey;
	    }
	    $contentKeysStr = substr($contentKeysStr, 1);
            $que7="insert into `article_keys` values ($keyid, $articleid, '$titleKeysStr', '$contentKeysStr')";
	    
	    //insert default trust values for articles from sources
	    //source trust can be defined from total of articles source rating in future
	    if ($oldSourceID !== 0) {
		$sourceTrustQ = "select `trust` from `source_trust` where `source_id` = $oldSourceID";
	    } else {
		$sourceTrustQ = "select `trust` from `source_trust` where `source_id` = $sourceNewID";
	    }
//	    $sourceTrustArr = mysql_query($sourceTrustQ, $dbconnect);
//            $sourceTrustArr = mysql_fetch_assoc($sourceTrustArr);
	    
	    $sourceTrustArr = sql_query($sourceTrustQ );
	    
	    //
	    $artKeyQuery = "select max(id) as id from article_trust";
            $artKeyarr = mysql_query($artKeyQuery, $dbconnect);
            $artKeyarr = mysql_fetch_assoc($artKeyarr);
	    
	    $artKeyarr = sql_query("select max(id) as id from article_trust");
	    
	    if ($artKeyarr['id'] == false) { $artKeyarr['id'] = 0; } 
            $artid = $artKeyarr['id'] + 1;
            $que8="insert into `article_trust` values ($id, $articleid, ".$sourceTrustArr['trust'].")";
	    

	    //insert into topics or find topic
	    $topicQuery = "select max(id) as maxid, max(topic_id) as maxtopicid, `id`, `topic_keys`, `topic_id`
				from topic order by `topic_id` desc";
            $topicArr = mysql_query($topicQuery, $dbconnect);
            $topicArr = mysql_fetch_assoc($topicArr);
	    
	    $topicArr  = sql_query("select max(id) as maxid, max(topic_id) as maxtopicid, `id`, `topic_keys`, `topic_id`
				from topic order by `topic_id` desc");
	    
            $topicid = $topicArr['maxid'] + 1;
            $topicid2 = $topicArr['maxtopicid'] + 1;
//	    $topicQuerya = "select max(id) as maxid, max(topic_id) as maxtopicid, `id`, `topic_ID`, `article_id` from topic_article";
//            $topicArra = mysql_query($topicQuerya, $dbconnect);
//            $topicArra = mysql_fetch_assoc($topicArra);
	    
	    $topicArra = sql_query("select max(id) as maxid, max(topic_id) as maxtopicid, `id`, `topic_ID`, `article_id` from topic_article");
	    
            $topicida = $topicArra['maxid'] + 1;
            $topicid2a = $topicArra['maxtopicid'] + 1;
            //need to add a check to see if topic exists
	    $topicKeys = array();
	    $topicKeys = array_merge($titleKeys, $contentKeys);
	    $topicKeys = array_unique($topicKeys);
	    $topicKeys = array_filter($topicKeys);
	    $topicKeysStr = '';
	    $topicIDold = compare_topics($nounKeys, $dbconnect);
	    if ($noOfTopicKeys > count($topicKeys)) {
		if (count($topicKeys) >= 5) {
			$noOfTopicKeys = count($topicKeys);
		} else {
			$topicIDold = 0;
		}
	    }
	    for ($i=0; $i<$noOfTopicKeys;$i++) {
		$topicKeysStr = $topicKeysStr.','.$topicKeys[$i];
	    };
	    
	    $topicKeysStr = substr($topicKeysStr, 1);
	    if ($topicIDold == -1) {
		$que4="insert into `topic` values ($topicid, $topicid2, '$topicKeysStr')";
		$que5="insert into `topic_article` values ($topicida, $topicid2a, $articleid)";
	    } else {
		$que5="insert into `topic_article` values ($topicida, $topicIDold, $articleid)";
	    }
            
            //need to put these queries in a function to stop repetition
            $var1 = mysql_query($que1, $dbconnect);
            $var1 = mysql_fetch_assoc($var1);
            $var2 = mysql_query($que2, $dbconnect);
            $var2 = mysql_fetch_assoc($var2);
            $var3 = mysql_query($que3, $dbconnect);
            $var3 = mysql_fetch_assoc($var3);
	    $var4 = mysql_query($que4, $dbconnect);
            $var4 = mysql_fetch_assoc($var4);
	    if (!is_null($que5)) {
		$var5 = mysql_query($que5, $dbconnect);
		$var5 = mysql_fetch_assoc($var5);
	    }
	    $var6 = mysql_query($que6, $dbconnect);
            $var6 = mysql_fetch_assoc($var6);
	      $var7 = mysql_query($que7, $dbconnect);	
            $var7 = mysql_fetch_assoc($var1);
	      $var8 = mysql_query($que8, $dbconnect);	
            $var8 = mysql_fetch_assoc($var1);
	      $var9 = mysql_query($que9, $dbconnect);	
            $var9 = mysql_fetch_assoc($var1);
	    
	    echo "<br />Query 1: $que1<br />";
	    echo "<br />Query 2: $que2<br />";
	    echo "<br />Query 3: $que3<br />";
	    echo "<br />Query 4: $que4 topic keys <br />";
	    echo "<br />Query 5: $que5<br />";
	    echo "<br />Query 6: $que6<br />";
	    echo "<br />Query 7: $que7<br />";
	    echo "<br />Query 8: $que8<br />";
	    echo "<br />Query 9: $que9<br />";
	    
            if (($var1 == 'false') || ($var2 == 'false') ||
		($var3 == 'false') || ($var4 == 'false') ||
		($var5 == 'false') || ($var6 == 'false') ||
		($var7 == 'false') || ($var8 == 'false') ||
		($var9 == 'false') ) {
                echo 'Something went wrong and data was not inserted';
            }
	    
	    echo "Finished processing.";
        }
        $stop = true;
}
else {
	echo "Error: " . magpie_error();
}

function clean_HTML($string) {
    $newStr = $string;
    $stop = strpos($newStr, '<!');
    $count=1;
    if ($stop == 0) {
	$newStr = str_replace('<![CDATA[', '', $newStr);
	$newStr = str_replace(']]>', '', $newStr);
    }
    $start = strpos($newStr, '<');
    $end = strpos($newStr, '>');
    
    while ($start != false) {
	//$start = $start - 1;
        $end = $end;
        $len = ($end - $start) + 1;
        $newStrRep = substr($newStr, $start, $len);
        $newStr = str_replace($newStrRep, '', $newStr);
        $count++;
	$start = strpos($newStr, '<');
	$end = strpos($newStr, '>');
        //break;
    }
    
    $newStr = strtolower($newStr);
    return($newStr);
}

function get_nouns($array) {
    $nounArray = array();
    foreach ($array as $item) {
	$item = preg_replace('[\W]', '', $item);
        $noun = testWordNet($item);	
        if ($noun == 1) {
            array_push($nounArray, $item);
        }
    }
    
    return($nounArray);
}

function testWordNet($word) {
    $noun = true;
    $ifcommon = false;
    $path = '/usr/local/WordNet-3.0/bin/';
    $wb = $path.'wn';
    $verb = ' -domnv';
    $adj = ' -domna';
    $adverb = ' -domnr';
    exec($wb.' '.$word.$verb, $res);
    exec($wb.' '.$word.$adj, $res2);
    exec($wb.' '.$word.$adverb, $res3);
    $common = array('the', 'their', 'a', 'with', 'of', 'this', 'them', 'how', 'its', 'where', 'when',
		    'at', 'an', 'our', 'and', 'we', 'you', 'your', 'to', ' ', 'for', '', 'or',
		    'from', 'it', 'while', 'that', 'he', 'his', 'she', 'hers', 'has', 'but', 'her',
		    'they', 'than', 'what');
    foreach ($common as $com) {
	$word = trim($word);
	$com = trim($com);
	if (strcmp($word,$com) == 0) {
		$ifcommon = true;
	}
    }
    
    //echo "<br />Wordnet item $word --- res1: ".count($res)." res2: ".count($res2)." res3: ".count($res3)." and common ".var_dump($ifcommon)."<br />";
    
    if ((count($res) > 0) || (count($res2) > 0) || (count($res3) > 0)) { $noun = false; }
    if ($ifcommon == true) { $noun = false; }
    
    return($noun);
    
}

//function get_keys ($keyContent) {
//    $keysArr = $keyContent;
//    
//    $popArray = array();
//    foreach ($keysArr as $keyPop) {
//    $keyPopCount = 0;
//    $keyPopArr = explode(',', $keyPop);
//        foreach ($keysArr as $key) {
//            if ($key == $keyPopArr[0]) {
//                $keyPopCount++;
//            }
//        }
//    array_push($popArray, ($keyPopArr[1]+ $keyPopCount).'-'.$keyPopArr[0]);
//    }
//    $popArray = array_unique($popArray);
//    array_multisort($popArray, SORT_DESC);
//    $popArrayNew = array();
//    foreach ($popArray as $pop) {
//        $pos = strpos($pop, '-');
//        $pop = substr($pop,$pos+1);
//	array_push($popArrayNew,$pop);
//    }
//    return($popArrayNew);
//}

function compare_topics($nounKeys, $dbconnect) {
	//nounKeys - keys in array from content, topicArr - select * from topic
	
//	$topTopicIDQ = "select max(`id`) as maxid from topic";
//        $topTopicID = mysql_query($topTopicIDQ, $dbconnect);
//        $topTopicID = mysql_fetch_assoc($topTopicID);
	
	$topTopicID = sql_query("select max(`id`) as maxid from topic");
	
	//echo "COMPARE TOPICS BEING CALLED $topTopicIDQ | toptopicid ".var_dump($topTopicID);
	//$topicIDold = 0;
	$topTopicID = $topTopicID['maxid'];
	//$bestMatchID(topic id, number of matches)
	$bestMatchID = array(0, 0);
	$topicID = -1;
	
	//100 for the number of topics to go back in searching
	for ($i=$topTopicID; $i>($topTopicID - 200); $i=$i-1) {
		//$topTopicQ = "select * from topic where id = $i";
		//$topTopic = mysql_query($topTopicQ, $dbconnect);
		//$topTopic = mysql_fetch_assoc($topTopic);
		
		$topTopic = sql_query("select * from topic where id = $i");
		
		$topicKeys = explode(',', $topTopic['topic_keys']);
		$topicKeysCount = count($topicKeys);
		$equal = array_intersect($nounKeys, $topicKeys);
		//echo "<br />#!# query | $topTopicQ | all topics array";
		//var_dump($topicKeys);
		//echo "#!#<br />";
		
		$perc = (count($equal) / $topicKeysCount) * 100;
		
		//echo "<br />## EQUAL = ".count($equal)."; PERC on compare $perc; topic count $topicCount;
		//topic arr ".print_r($topicArr)."; count noun keys ".count($nounKeys).";
		//count topic keys ".count($topicKeys)."##<br />";
		//
		if ($perc >= 40) { //keeping this  at 40% for testing, to see grouping etc. after testing, articles do not get grouped
					//properly at high numers, i.e. new topics are created too soon and do not group properly
					//on callback. rather group easily then call back only high groupings?
			if (count($equal) > $bestMatchID[1]) {
				$bestMatchID = array($topTopic['topic_id'], count($equal));
			}
			$topicID = $bestMatchID[0];
		}
	}
	
	return($topicID);
}

?>
