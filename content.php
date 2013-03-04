<?php
/*
Get top 10 topics
display top ten topics in top wrapper divs. topic ids as div ids
### topics must have at least 3 articles (pref one under each column)
for each topic, show 15 articles
split articles into trust

#########################
checking for rss:
have a function check all rss sources every 5/10 minutes for new articles
process only new articles
if added to new topic already on front page, refresh that topic
if new topic created, wait until enough articles then add to page


*/

//open the db
$database =  "newsbias_nb";
$dbconnect = mysql_pconnect('localhost', 'newsbias_nbadmin', 'password');
mysql_select_db($database, $dbconnect);

//get_articles($dbconnect, 35);
$topics = get_topics($dbconnect);
//echo $topics;

function get_topics($dbconnect) {
    //$topicsCountQ = "select distinct topic_id, topic_keys from article_topic_view where topic_id <> 0
    //order by article_id desc limit 10";
    $topicsCountQ = "select topic_id, topic_keys from article_topic_view
    where topic_id <> 0
    group by topic_id
    having count(topic_id) > 1
    order by article_id desc";
    $topicsCountArr = mysql_query($topicsCountQ, $dbconnect);
    $topicsCount = mysql_num_rows($topicsCountArr);
    //echo $topicsCountArr;
    
    $returnStr = "";
    
    for ($i = $topicsCount; $i > 0; $i--) {
        //$topicsQ = "select * from topic where `topic_id` = $i";
        //$topicsArr = mysql_query($topicsQ, $dbconnect);
        //$topicsArr = mysql_fetch_assoc($topicsArr);
        
        $topicsArr = mysql_fetch_row($topicsCountArr);
        $returnStr.="<div class='topicWrapper'>";
        $returnStr.="<div id='topic".$topicsArr[0].
        "' class='topicTitle'>Topic: ".str_replace(",", " ", $topicsArr[1])."</div><br />";
        $articles = get_articles($dbconnect, $topicsArr[0]);
        $returnStr.=$articles;
        $returnStr.="</div>";
    }
    
    echo $returnStr;
}

function get_articles($dbconnect, $topicID) {
    $articlesQ = "select distinct am.`article_title` as title,
                    am.`article_content` as content,
                    am.`article_id` as articleid
                    from article_meta am, topic_article ta
                    where am.`article_id` = ta.`article_id` and
                    ta.`topic_id` = $topicID order by am.`article_id` desc";
    $articlesArrQ = mysql_query($articlesQ, $dbconnect);
    $articlesArr = mysql_num_rows($articlesArrQ);
    
    $articlesLeft = "<div class='articleWrapperLeft'>";
    $articlesRight = "<div class='articleWrapperRight'>";
    
    $countLeft = 0;
    $countRight = 0;
    for ($j = 0 ; $j < $articlesArr ; $j++) {
        $item = mysql_fetch_row($articlesArrQ);
        $trust = get_article_trust($item[2], $dbconnect);
        if (($trust <= 4) && ($countLeft<=5)) {
            $articlesLeft.="<div id='article$item[2]' class='articleWrapper left'>";
            $articlesLeft.= "<div class='title'>$item[0]</div>";
            $articlesLeft.= "<div class='content'>$item[1]<div class='more'>Read more</div>";
            $articlesLeft.=get_form($item[2])."<div class='curTrust'>Current trust: $trust</div>";
            $articlesLeft.="</div>";
            $articlesLeft.="</div>";
            $countLeft++;
        } elseif (($trust >= 5) && ($countRight<=5)) {
            $articlesRight.="<div id='article$item[2]' class='articleWrapper right'>";
            $articlesRight.= "<div class='title'>$item[0]</div>";
            $articlesRight.= "<div class='content'>$item[1]<div class='more'>Read more</div>";
            $articlesRight.=get_form($item[2])."<div class='curTrust'>Current trust: $trust</div>";
            $articlesRight.="</div>";
            $articlesRight.="</div>";
            $countRight++;
        }
    }
    
    if ($countLeft == 0) {
        $articlesLeft.="<div id='article-no' class='articleWrapper left'>";
        $articlesLeft.= "There are no articles rated to be biased.";
        $articlesLeft.="</div>";
    } elseif ($countRight == 0) {
        $articlesRight.="<div id='article-no' class='articleWrapper right'>";
        $articlesRight.= "There are no articles rated to be trustworthy.";
        $articlesRight.="</div>";
    }
    
    $articlesLeft.= "</div>";
    $articlesRight.= "</div>";
    
    $articles=$articlesLeft.$articlesRight;
    
    return $articles;
}

function get_form($id) {
    $str = "<form id='$id' class='voteForm'>";
    $str.="<label>Vote: </label>";
    $str.= "<select type='select' class='selectRating'>";
    for ($i = 1; $i <= 10; $i++) {
        $str.=  "<option>$i</option>";
    }
    $str.= "</select>";
    $str.="<input class='voteSubmit' type='submit' value='Submit Vote' />";
    $str.="<div class='response'></div>";
    $str.= "</form>";
    return($str);
}

function get_article_trust($articleID, $dbconnect) {
    mysql_select_db($database, $dbconnect);
    $trustQ = "select trust from article_trust where article_id = $articleID";
    $trustArrQ = mysql_query($trustQ, $dbconnect);
    $trustArr = mysql_num_rows($trustArrQ);
    $total = 0;
    
    for ($j = 0 ; $j < $trustArr ; $j++) {
        $item = mysql_fetch_row($trustArrQ);
        $total = $total + $item[0];
    }
    
    if ($trustArr == 0) $trustArr = 1;
    $avgtrust = $total / $trustArr;
    $avgtrust = round($avgtrust, 1);
    return $avgtrust;
}

mysql_close($dbconnect);

?>
