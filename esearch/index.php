<?php
require '../init.php';
?>

<h2>Search</h2>

<form action="index.php" method="get">
<table border='0'>
	<tr>
		<th>Query</th><th>Options</th><th>Search in</th>
	</tr>
	<tr valign="top" cellspacing="10" cellpadding="20">
		<td>
			
			<input type="text" name="query" value="<?php echo $_GET['query'] ?>" size="20"><br>
		</td>
		<td>
			
			<input type="radio" name="fuzzy" value="0" <?php if($_GET['fuzzy']=='0'){echo "checked";}?>>exact Search<br>
			<input type="radio" name="fuzzy" value="1" <?php if($_GET['fuzzy']=='1' || $_GET['fuzzy']==""){echo "checked";}?>>fuzzy Search<br>
		</td>
		<td>
			<input type="checkbox" name="field" value="authors" checked=true>Authors<br>
			<input type="checkbox" name="field" value="publications" checked=true>Publications<br>
			<input type="checkbox" name="field" value="topics" checked=true>Topics<br>
			<input type="checkbox" name="field" value="tags" checked=true>Tags<br>
		</td>
	</tr>
</table>
<input type="hidden" name="task" value="search">
<input type="submit" value="Search">
</form>



<?php
switch ($_GET['task']) {
	case 'search':
		//print header
		echo "<br><br><h2>Results</h2><hr>";
		//init search client
		require 'vendor/autoload.php';				//load the php-elastic search API
		$client = new Elasticsearch\Client();		//create client
		//define search parameters
		$searchParams = []; 
		$searchParams['index'] = 'jdbc';
		$searchParams['type'] = 'jdbc';
		
		if($_GET['fuzzy']=='1'){
			$searchParams['body']['query']['bool']['should']['fuzzy']['_all'] = $_GET['query'];	//fuzzy search
		} else {
			$searchParams['body']['query']['match']['_all']['query'] = $_GET['query'];			//exact search
		}

		$searchParams['body']['sort'] = ['_score'];
		$searchParams['body']['size'] = 100;
		// the actual query. Results are stored in a PHP array
		$res = $client->search($searchParams);
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
				array_push($topics, $source['topic_id']);
				array_push($topics_name, $source['name']);
			}
			if(array_key_exists('tag_id',$source)){
				array_push($tags, $source['tag_id']);
				array_push($tags_name, $source['tag']);
			}
			if(array_key_exists('author_id',$source)){
				array_push($authors, $source['author_id']);				
				array_push($authors_firstname, $source['firstname']);
				array_push($authors_lastname, $source['surname']);	
			}

		}


		$topics_content = "<span style='font-size:large;'>List contains <b>".sizeof($topics)." Topics</b></span>";
		for($i = 0; $i < sizeof($topics);$i++){
			$topics_content = $topics_content . "<br><a href='".BIBLIOGRAPHIE_WEB_ROOT . "/topics/?task=showTopic&topic_id=".$topics[$i]."'>".$topics_name[$i]."</a>";
		}
		
		$tags_content = "<span style='font-size:large;'>List contains <b>".sizeof($tags)." Tags</b></span>";
		for($i = 0; $i < sizeof($tags);$i++){
			$tags_content = $tags_content . "<br><a href='".BIBLIOGRAPHIE_WEB_ROOT . "/tags/?task=showTag&tag_id=".$tags[$i]."'>".$tags_name[$i]."</a>";
		}
		$authors_content = "<span style='font-size:large;'>List contains <b>".sizeof($authors)." Authors</b></span>";
		for($i = 0; $i < sizeof($authors);$i++){
			$authors_content = $authors_content . "<br><a href='".BIBLIOGRAPHIE_WEB_ROOT . "/authors/?task=showAuthor&author_id=".$authors[$i]."'>".$authors_firstname[$i]." ".$authors_lastname[$i]."</a>";
		}
		echo "
		<table style='box-shadow: 5px 5px 5px #AAAAAA;padding: 5px;    margin-left: auto;margin-right: auto;'><tr>
		<td id='PubListBox' class='active_cell' onmouseover='showLink()'>".sizeof($pubs)." Hits in Publications</td>
		<td id='PubListBox' class='inactive_cell'>".sizeof($topics)." Hits in Topics</td>
		<td id='PubListBox' class='inactive_cell'>".sizeof($tags)." Hits in Tags</td>
		<td id='PubListBox' class='inactive_cell'>".sizeof($authors)." Hits in Authors</td>
		</tr></table>
		
		<br>
		<div id='PubList'>
		".
		bibliographie_publications_print_list2($pubs, BIBLIOGRAPHIE_WEB_ROOT . '/topics/?task=showPublications&topic_id=' . 77777 . '&includeSubtopics=1')

		."
		</div><br><hr>
		<div id='TopList'>
		".
		$topics_content
		."
		</div><br><hr>
		<div id='TagList'>
		".
		$tags_content
		."
		</div><br><hr>
		<div id='AuthorList'>
		".
		$authors_content
		."
		</div>
		";



		
	break;
}

require BIBLIOGRAPHIE_ROOT_PATH . '/close.php';
