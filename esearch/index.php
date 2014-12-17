<?php
require '../init.php';
?>

<h2>Search</h2>

<form action="index.php" method="get">
	<!-- search parameters -->
<table border='0' align="center">
	<tr>
		<th>Query</th><th>Modifiers</th>
	</tr>
	<tr valign="top" cellspacing="10" cellpadding="20">
		<td>
			<?php
			if(empty($_GET['start'])){
				$start = '0';
			} else {
				$start = $_GET['start'];
			}
			if(empty($_GET['end'])){
				$end = '100';
			} else {
				$end = $_GET['end'];
			}
			
			?>
			<input type="text" name="query" value="<?php echo $_GET['query'] ?>" size="30"><br>
			Show Hits<input type="text" name="start" value="<?php echo $start ?>" size="3">
			 to<input type="text" name="end" value="<?php echo $end ?>" size="3"><br>
			 
			 <small>Newly created entries, are indexed after 1 minute.</small>
		</td>
		<td>
			Allowd modifiers are<b> AND</b>, <b>OR</b>, <b>NOT</b>.<br>
			<b>query~1</b> == Fuzzy Search with max distance 1<br>
			<b>/query/</b> == <a href="http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/query-dsl-regexp-query.html#regexp-syntax">regular expressions</a> in query allowed<br>
			<b>+query query2</b> == query1 must be in hit, query 2 can be<br>
			<b>"query1 query2"</b> == search sentence<br>
			<b>query1^4 query2</b> == query1 4 times more important<br>
			<b>field:query</b> == search in field (e.g. surname, firstname, year, pub_type, volume, publisher, journal)<br>
			The modifiers can be combined with brackets <b>(</b>,<b>)</b>.
		
			
		</td>
	</tr>
</table>
<input type="hidden" name="task" value="search">
<input type="submit" value="Search">
</form>



<?php
switch ($_GET['task']) {
	case 'search':
		// append search to 
		bibliographie_history_append_step('search', 'Seaching' . htmlspecialchars($publication['title']));

	
		//print header
		echo "<br><br><h2>Results</h2><hr>";
		//init search client
		require 'vendor/autoload.php';				//load the php-elastic search API
		$client = new Elasticsearch\Client();		//create client
		//define search parameters
		$params = array( 	'index' =>"jdbc", 		//see also http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/query-dsl-queries.html
							'type' =>"jdbc", 
							'body' => array( 
								'query' => array(
									'query_string' => array( 
										'query' => $_GET['query'], 
									),
							
								) 
							) 
						); 
		$params['body']['sort'] = ['_score'];
		$params['body']['size'] = 1000;

		// the actual query. Results are stored in a PHP array
		$res = $client->search($params);		//execute search
		$hits = $res['hits']['hits'];
		
		
		$pubs = array();			//here the pub_id of hits are saved
		
		$topics = array();			//here the topic_id of hits are saved
		$topics_name = array();		//the name of the topic
		
		$authors = array();			//here the author_id of hits are saved
		$authors_firstname = array();
		$authors_lastname = array();
		
		$tags = array();			//here the tag_id of hits are saved
		$tags_name = array();
		
		$pub_counter = 0;
		for($i=0; $i < sizeof($hits);$i++){
			$source = $hits[$i]['_source'];
			if(array_key_exists('pub_id',$source)){
				array_push($pubs, $source['pub_id']);
			}
			if(array_key_exists('topic_id',$source)){
				array_push($topics, array($source['topic_id'], $source['name']));
			}
			if(array_key_exists('tag_id',$source)){
					array_push($tags, array($source['tag_id'], $source['tag']));				
			}
			
			if(array_key_exists('author_id',$source)){
				array_push($authors, array($source['author_id'], $source['firstname'], $source['surname']));			
			}

		}

		//erase doubles
		$shortauthors = array();
		for($i = 0; $i < sizeof($authors);$i++){
			$comp = $authors[$i];
			$isin = FALSE;
			for($j = 0; $j < sizeof($shortauthors); $j++){
				if(strcmp($shortauthors[$j][0],$comp[0])==0 && strcmp($shortauthors[$j][1],$comp[1])==0 && strcmp($shortauthors[$j][2],$comp[2])==0){
					$isin = TRUE;
				}
			}
			if(! $isin){
				array_push($shortauthors, $comp);
			}
		}
		$authors = $shortauthors;
		
		$shorttags = array();
		for($i = 0; $i < sizeof($tags);$i++){
			$comp = $tags[$i];
			$isin = FALSE;
			for($j = 0; $j < sizeof($shorttags); $j++){
				if(strcmp($shorttags[$j][0],$comp[0])==0 && strcmp($shorttags[$j][1],$comp[1])==0){
					$isin = TRUE;
				}
			}
			if(! $isin){
				array_push($shorttags, $comp);
			}
		}
		$tags = $shorttags;
		
		$shorttopics = array();
		for($i = 0; $i < sizeof($topics);$i++){
			$comp = $topics[$i];
			$isin = FALSE;
			for($j = 0; $j < sizeof($shorttopics); $j++){
				if(strcmp($shorttopics[$j][0],$comp[0])==0 && strcmp($shorttopics[$j][1],$comp[1])==0){
					$isin = TRUE;
				}
			}
			if(! $isin){
				array_push($shorttopics, $comp);
			}
		}
		$topics = $shorttopics;

		//cut to length of max hits ($end)
		$pubs = array_slice($pubs,$start,$end-$start + 1);
		$topics = array_slice($topics,$start,$end-$start + 1);
		$tags = array_slice($tags,$start,$end-$start + 1);
		$authors = array_slice($authors,$start,$end-$start + 1);




		//make output-strings from arrays
		$topics_content = "<span style='font-size:larger;'>List contains <b>".sizeof($topics)." Topics</b></span>";
		for($i = 0; $i < sizeof($topics);$i++){
			$topics_content = $topics_content . "<br>Hit: ".($i + intval($start))." <a href='".BIBLIOGRAPHIE_WEB_ROOT . "/topics/?task=showTopic&topic_id=".$topics[$i][0]."'>".$topics[$i][1]."</a>";
		}	
		$tags_content = "<span style='font-size:larger;'>List contains <b>".sizeof($tags)." Tags</b></span>";
		for($i = 0; $i < sizeof($tags);$i++){
			$tags_content = $tags_content . "<br>Hit: ".($i + intval($start))." <a href='".BIBLIOGRAPHIE_WEB_ROOT . "/tags/?task=showTag&tag_id=".$tags[$i][0]."'>".$tags[$i][1]."</a>";
		}
		$authors_content = "<span style='font-size:larger;'>List contains <b>".sizeof($authors)." Authors</b></span>";
		for($i = 0; $i < sizeof($authors);$i++){
			$authors_content = $authors_content . "<br>Hit: ".($i + intval($start))." <a href='".BIBLIOGRAPHIE_WEB_ROOT . "/authors/?task=showAuthor&author_id=".$authors[$i][0]."'>".$authors[$i][1]." ".$authors[$i][2]."</a>";
		}
		//print it all
		echo "
		<table style='box-shadow: 5px 5px 5px #AAAAAA;padding: 5px;    margin-left: auto;margin-right: auto;'><tr>
		<td id='PubListBox' class='active_cell' onclick='showSearchPub()'>".sizeof($pubs)." Hits in Publications</td>
		<td id='TopListBox' class='inactive_cell' onclick='showSearchTop()'>".sizeof($topics)." Hits in Topics</td>
		<td id='TagListBox' class='inactive_cell' onclick='showSearchTag()'>".sizeof($tags)." Hits in Tags</td>
		<td id='AuthorsListBox' class='inactive_cell' onclick='showSearchAuthor()'>".sizeof($authors)." Hits in Authors</td>
		</tr></table>
		
		<br>
		<div id='PubList'>
		".
		bibliographie_publications_print_noorder($pubs, BIBLIOGRAPHIE_WEB_ROOT . '/topics/?task=showPublications&topic_id=' . 77777 . '&includeSubtopics=1',array(),$start)

		."
		</div>
		<div id='TopList'>
		".
		$topics_content
		."
		</div>
		<div id='TagList'>
		".
		$tags_content
		."
		</div>
		<div id='AuthorList'>
		".
		$authors_content
		."
		</div>
		";



		
	break;
}

require BIBLIOGRAPHIE_ROOT_PATH . '/close.php';
