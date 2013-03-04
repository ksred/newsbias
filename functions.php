<?php
$path = $_SERVER["DOCUMENT_ROOT"];
$path = dirname(__FILE__) . '/';

function db_connect() {
    $database =  "newsbias_nb";
    $dbconnect = mysql_pconnect('localhost', 'newsbias_nbadmin', 'password');
    if (!$dbconnect) {
        echo ("DB connect failed".mysql_error());
    }
    mysql_select_db($database, $dbconnect);
    
    return ($dbconnect);
}

function sql_query($queryString) {
    $dbconnect = db_connect();
    $var = mysql_query($queryString, $dbconnect);
    $var = mysql_fetch_assoc($var);
    return($var);
}

function rss_content() {
    //Get topics
    $topicsQ = 'select count(*) as "count" from topic';
    $topics = sql_query($topicsQ);
    //$topicKeys = $topics['topic_keys'];
    //$topicKeysArr = explode(',', $topicKeys);
    
    $topicCount = $topics['count'];
    for ($i=0; $i < $topicCount; $i++) {
        //echo test().$i.'<br />';
        
        ////Get articles per topic
        $articleQ = 'select * from `article_keys` where `article_id` = 1';
        $article = sql_query($articleQ);
        //Get keys from title
        $titleKeysArr = explode(',', $article['title_keys']);
        
        //Get keys from content
        $contentKeysArr = explode(',', $article['content_keys']);
        
        //Get popularity of keys
        $articleKeysArr = array_merge($titleKeysArr, $contentKeysArr);
        //var_dump($articleKeysArr);
        
        $popArray = array();
        foreach ($articleKeysArr as $keyPop) {
        $keyPopCount = 0;
        $keyPopArr = explode(',', $keyPop);
            foreach ($articleKeysArr as $key) {
                if ($key == $keyPopArr[0]) {
                    $keyPopCount++;
                }
            }
        array_push($popArray, ($keyPopArr[1]+ $keyPopCount).','.$keyPopArr[0]);
        }
        //echo '<br /><br />';
        $popArray = array_unique($popArray);
        array_multisort($popArray, SORT_DESC);
        //var_dump($popArray);
        
        //array_intersect();
        
    }//end topics loop
    
}

function get_top_topics() {
    $que = 'select top 10 t.topic_keys, t.topic_id from article a, topic_article ta, topic t
    where a.article_id = ta.article_id and t.topic_id = ta.topic_id 
    order by a.date desc';
    
    $topics = sql_query($que);
    
    foreach ($topics as $topic) {
        top_five_per_topic($topic['topic_id']);
    }
}

function top_five_per_topic ($topicID) {
    $que = 'select top 5 am.`article_title` as title, am.`article_content` as content from article_meta am, topic_article ta
    where am.article_id = ta.article_id and ta.topic_id = '.$topicID;
    
    $content = sql_query($que);
    
    //get keys to compare to topic keys
    $totalContentArr = array_merge($titleKeysArr, $contentKeysArr);
    $totalContentKeysArr = get_keys($totalContentArr);
    
    //get topic keys
    $que = "select * from topic where topic_id = $topicID";
    $topic = sql_query($topic);
    $topicKeysArr = get_keys($topic);
    
    //compare the two to see if they are a match
    $common = compare($totalContentKeysArr, $topicKeysArr);
    if ($common) {
        //print out article
        echo $content['title'];
        echo $content['content'];
    }
}

function get_keys ($keyContent) {
    $keysArr = $keyContent;
    
    $popArray = array();
    foreach ($keysArr as $keyPop) {
    $keyPopCount = 0;
    $keyPopArr = explode(',', $keyPop);
        foreach ($keysArr as $key) {
            if ($key == $keyPopArr[0]) {
                $keyPopCount++;
            }
        }
    array_push($popArray, ($keyPopArr[1]+ $keyPopCount).'-'.$keyPopArr[0]);
    }
    $popArray = array_unique($popArray);
    array_multisort($popArray, SORT_DESC);
    $popArrayNew = array();
    foreach ($popArray as $pop) {
        $pos = strpos($pop, '-');
        $pop = substr($pop,$pos+1);
  array_push($popArrayNew,$pop);
    }
    return($popArrayNew);
}

function compare($array1, $array2) {
    $newArr1 = array();
    $newArr2 = array();
    
    //reduce to top 10
    for ($i=0; $i<10; $i++) {
        array_push($newArr1, $array1);
        array_push($newArr2, $array2);
    }
    
    $commonArr = array_intersect($newArr1,$newArr2);
    if (count($commonArr) > 5 ) {
        $common = true;
    } else {
        $common = false;
    }
    
    return($common);
}

function test () {
    //return 'this is a test from functions.php';
}

function list_topics() {
    $dbconnect = db_connect();
    //$que = 'select distinct topic_id, topic_keys from article_topic_view where topic_id <> 0
    //order by article_id desc';
    $que = "select topic_id, topic_keys from article_topic_view
    where topic_id <> 0
    group by topic_id
    having count(topic_id) > 1
    order by article_id desc";
    $tArr = mysql_query($que, $dbconnect);
    $tArrCount = mysql_num_rows($tArr);
    
    for ($i = 0; $i < $tArrCount; $i++) {
        $topic = mysql_fetch_row($tArr);
        //$topic = str_replace(',', ' ', $topic);
        echo "<div id='".$topic[0]."' class='topicItem'>".$topic[1]."</div>";
    }
   
}

function list_sources() {
    $dbconnect = db_connect();
    //$que = 'select distinct topic_id, topic_keys from article_topic_view where topic_id <> 0
    //order by article_id desc';
    $que = "select * from source";
    $tArr = mysql_query($que, $dbconnect);
    $tArrCount = mysql_num_rows($tArr);
    
    for ($i = 0; $i < $tArrCount; $i++) {
        $source = mysql_fetch_row($tArr);
        //$topic = str_replace(',', ' ', $topic);
        echo "<div id='".$source[0]."' class='topicItem'><a href='".$source[3]."' target='_blank'>".$source[2]."</a></div>";
    }
   
}

function all_per_topic($topicID) {
    echo "articles all per topic";
    $dbconnect = db_connect();
    $articlesQ = "select distinct am.`article_title` as title,
                    am.`article_content` as content,
                    am.`article_id` as articleid,
                    at.trust as trust
                    from article_meta am, topic_article ta, article_trust at
                    where am.`article_id` = ta.`article_id` and at.`article_id` = am.`article_id`
                    and at.`article_id` = ta.`article_id` and
                    ta.`topic_id` = $topicID order by am.`article_id` desc limit 10";
    $articlesArrQ = mysql_query($articlesQ, $dbconnect);
    $articlesArr = mysql_num_rows($articlesArrQ);
    
    $articlesLeft = "<div class='articleWrapperLeft'>";
    $articlesRight = "<div class='articleWrapperRight'>";
    
    $countLeft = 0;
    $countRight = 0;
    for ($j = 0 ; $j < $articlesArr ; $j++) {
    $item = mysql_fetch_row($articlesArrQ);
    if ($item[3] <= 4) {
        $articlesLeft.="<div id='article$item[2]' class='articleWrapper left'>";
        $articlesLeft.= "<div class='title'>$item[0] and trust $item[3]</div>";
        $articlesLeft.= "<div class='content'>$item[1]<div class='more'>Read more</div></div>";
        $articlesLeft.="</div>";
        $countLeft++;
    } elseif ($item[3] >= 5) {
        $articlesRight.="<div id='article$item[2]' class='articleWrapper right'>";
        $articlesRight.= "<div class='title'>$item[0] and trust $item[3]</div>";
        $articlesRight.= "<div class='content'>$item[1]<div class='more'>Read more</div></div>";
        $articlesRight.="</div>";
        $countRight++;
    }
        //var_dump($item);
    }
    
    if ($countLeft == 0) {
        $articlesLeft.="<div id='article-no' class='articleWrapper left'>";
        $articlesLeft.= "There are no articles rated less than five.";
        $articlesLeft.="</div>";
    } elseif ($countRight == 0) {
        $articlesRight.="<div id='article-no' class='articleWrapper right'>";
        $articlesRight.= "There are no articles rated greater than five.";
        $articlesRight.="</div>";
    }
    
    $articlesLeft.= "</div>";
    $articlesRight.= "</div>";
    
    $articles=$articlesLeft.$articlesRight;
    
    return $articles;
    echo "Articles are pulling through";
}

function vote_form($id) {
    echo "<form id='$id' class='voteForm'>";
    echo "<input type='select'>";
    for ($i = 1; $i <= 10; $i++) {
        echo "<select>$i</select>";
    }
    echo "</input>";
    echo "</form>";
}

/*###############################################*/


?>
