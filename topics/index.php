<?php
require '../init.php';
?>

<h2>Topics</h2>
<?php
switch ($_GET['task']) {
  case 'deleteTopic':
    $topic = bibliographie_topics_get_data($_GET['topic_id']);

    if (is_object($topic)) {
      $parentTopics = bibliographie_topics_get_parent_topics($topic->topic_id);
      $subTopics = bibliographie_topics_get_subtopics($topic->topic_id);
      if (bibliographie_topics_delete($topic->topic_id))
		echo '<p class="success">The topic has been deleted!</p>';
	  else
        echo '<p class="error">An error occurred!</p>';
     }
    break;

  case 'topicEditor':
    $bibliographie_title = 'Topic editor';
    ?>

    <h3>Topic editor</h3>
    <?php
    $done = false;
    $topic = null;

    if (!empty($_GET['topic_id']) and !in_array($_GET['topic_id'], bibliographie_topics_get_locked_topics()))
      $topic = (array) bibliographie_topics_get_data($_GET['topic_id']);

    if (is_array($topic))
      bibliographie_history_append_step('topics', 'Editing topic ' . $topic['name']);
    else
      bibliographie_history_append_step('topics', 'Topic editor');


    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
      if (is_array($topic)) {
        $_POST = $topic;

        $topics = bibliographie_topics_get_parent_topics($_GET['topic_id']);
        if (is_array($topics) and count($topics) > 0)
          $_POST['topics'] = implode(',', $topics);
      }
      else
        $_POST['topics'] = 1;
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      $errors = array();

      if (empty($_POST['name']))
        $errors[] = 'You did not fill a name!';

      if (!empty($_POST['url']) and !is_url($_POST['url']))
        $errors[] = 'The URL you filled is not valid.';

      $topics = csv2array($_POST['topics']);
      if (count($errors) == 0) {
        if (is_array($topic)) {
          if (bibliographie_topics_edit_topic($topic['topic_id'], $_POST['name'], $_POST['description'], $_POST['url'], $topics)) {
            echo '<p class="success">Topic has been edited.</p>';
            echo 'You can <a href="' . BIBLIOGRAPHIE_WEB_ROOT . '/topics/?task=topicEditor&amp;topic_id=' . $topic['topic_id'] . '">return to the editor</a> or view the <a href="' . BIBLIOGRAPHIE_WEB_ROOT . '/topics/?task=showTopic&amp;topic_id=' . $topic['topic_id'] . '">topic page</a>.';
            $done = true;
          }
          else
            echo '<p class="success">Topic could not be edited.</p>';
        }else {
          if (bibliographie_topics_create_topic($_POST['name'], $_POST['description'], $_POST['url'], $topics)) {
            echo '<p class="success">Topic has been created.</p>';
            $done = true;
          }
          else
            echo '<p class="error">Topic could not be created!</p>';
        }
      }
      else
        bibliographie_print_errors($errors);
    }

    if (!$done) {
      $prePopulateTopics = array();

      /**
       * Fill the prePropulateTopics array.
       */
      if (!empty($_POST['topics'])) {
        if (preg_match('~[0-9]+(\,[0-9]+)*~', $_POST['topics'])) {
          $topics = csv2array($_POST['topics'], 'int');
          foreach ($topics as $parentTopic) {
            $prePopulateTopics[] = array(
              'id' => $parentTopic,
              'name' => bibliographie_topics_parse_name($parentTopic)
            );
          }
        }
      }
      ?>

      <p class="notice">On this page you can create and edit topics. Just fill at least the field for the name and hit save!</p>
      <?php
      if (is_array($topic)) {
        ?>

        <form action="<?php echo BIBLIOGRAPHIE_WEB_ROOT . '/topics/?task=topicEditor&amp;topic_id=' . $topic['topic_id'] ?>" method="post">
          <?php
        } else {
          ?>

          <form action="<?php echo BIBLIOGRAPHIE_WEB_ROOT . '/topics/?task=topicEditor' ?>" method="post">
            <?php
          }
          ?>

          <div class="unit">
            <div style="float: right; width: 50%">
              <label for="url" class="block">URL</label>
              <input type="text" id="url" name="url" value="<?php echo htmlspecialchars($_POST['url']) ?>" style="width: 100%" />
            </div>

            <label for="name" class="block"><?php echo bibliographie_icon_get('asterisk-yellow') ?> Name</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name']) ?>" style="width: 45%" />

            <div id="similarNameContainer" class="bibliographie_similarity_container"></div>
          </div>

          <div class="unit">
            <label for="description" class="block">Description</label>
            <textarea id="description" name="description" rows="6" cols="40" style="width: 100%"><?php echo htmlspecialchars($_POST['description']) ?></textarea>
          </div>

          <div class="unit">
            <label for="topics" class="block">Parent topics</label>
            <div id="topicsContainer" style="background: #fff; border: 1px solid #aaa; color: #000; float: right; font-size: 0.8em; padding: 5px; width: 45%;"><em>Search for a topic in the left container!</em></div>
            <input type="text" id="topics" name="topics" style="width: 100%" value="<?php echo htmlspecialchars($_POST['topics']) ?>" />
            <br style="clear: both" />
          </div>

          <div class="submit">
            <input type="submit" value="save" />
          </div>
        </form>

        <script type="text/javascript">
          /* <![CDATA[ */
          var topic_id = <?php
      if (is_array($topic))
        echo $topic['topic_id'];
      else
        echo 0;
      ?>;

          $(function() {
            bibliographie_topics_input_tokenized('topics', 'topicsContainer', <?php echo json_encode($prePopulateTopics) ?>);

            $('#name').bind('mouseup keyup', function() {
              delayRequest('bibliographie_topics_check_name', Array($('#name').val(), topic_id));
            });

            $('#content input, #content textarea').charmap();
          });
          /* ]]> */
        </script>
        <?php
        bibliographie_charmap_print_charmap();
      }
      break;

    case 'showTopic':
      $topic = bibliographie_topics_get_data($_GET['topic_id']);

      if (is_object($topic)) {
        bibliographie_history_append_step('topics', 'Showing topic ' . $topic->name);
        $bibliographie_title = 'Topic: ' . htmlspecialchars($topic->name);

        /**
         * Check locked topics.
         */
        $family = array_merge(array($topic->topic_id), bibliographie_topics_get_parent_topics($topic->topic_id, true));
        $lockedTopics = bibliographie_topics_get_locked_topics();
        if (is_array($lockedTopics) and count(array_intersect($family, $lockedTopics)) == 0)
		//header
          echo '<em style="float: right;">',
          ' <a href="javascript:;" onclick="bibliographie_topics_confirm_delete(' . $topic->topic_id . ');">' . bibliographie_icon_get('folder-delete') . ' Delete topic</a>',
          '</em>';
        else
          echo '<p class="notice">This or at least one of the parent topics is locked against editing. If you want to edit something regarding this topic please contact your admin!</p>';
        ?>

        <h3><?php echo bibliographie_topics_parse_name($topic->topic_id, array('linkProfile' => true)) ?></h3>

        <?php
        if (!empty($topic->description))
          echo '<p>' . htmlspecialchars($topic->description) . '</p>';
        ?>
		<!-- show publications and show including all subtopics -->
        <p>
          <?php echo count(bibliographie_topics_get_publications($topic->topic_id, false)) ?> Publications in this topic
          <!-- start of showpublications area -->
								<!-- the two buttons -->
          						<a id="hidepubbutton" onclick="		
									$('#pubbox').hide();
									$('#hidepubbutton').hide();	
									$('#showpubbutton').show();	
									"><?php echo bibliographie_icon_get_big('bullet-toggle-minus') ?></a>
								<a id="showpubbutton" onclick="
									$('#pubbox').show();
									$('#hidepubbutton').show();	
									$('#showpubbutton').hide();
								   "><?php echo bibliographie_icon_get_big('bullet-toggle-plus') ?></a>				   
		 <!-- the surrounding div -->
		  <div id="pubbox" style="background-color:#F8F8F8;">
			  <?php
			  $topic = bibliographie_topics_get_data($_GET['topic_id']);
			  if ($topic) {
				$includeSubtopics = '';
				$title = '';
				if ($_GET['includeSubtopics'] == 1) {
				  $includeSubtopics = '&includeSubtopics=1';
				  $title = ' and subtopics';
				}
				
				$array = bibliographie_topics_get_publications($topic->topic_id, (bool) $_GET['includeSubtopics']);
				
				echo $array[0];
				echo bibliographie_publications_print_list(
				  $array, BIBLIOGRAPHIE_WEB_ROOT . '/topics/?task=showPublications&topic_id=' . ((int) $_GET['topic_id']) . $includeSubtopics
				);
			  }
			  ?>
		  </div>
          <!-- end of show publications area -->
          
          
          
          <?php
          if (count(bibliographie_topics_get_subtopics($topic->topic_id, true)) > 0) {
            ?>

            <br />
            <a href="<?php echo BIBLIOGRAPHIE_WEB_ROOT ?>/topics/?task=showPublications&amp;topic_id=<?php echo (int) $topic->topic_id ?>&amp;includeSubtopics=1">
              <?php echo bibliographie_icon_get('page-white-stack') ?> Show publications including all subtopics
            </a> (<?php echo count(bibliographie_topics_get_publications($topic->topic_id, true)) ?>)
            <?php
          }
          ?>

        </p>
        <!-- show parent topics -->
        <?php
        $parentTopics = bibliographie_topics_get_parent_topics($topic->topic_id);
        if (count($parentTopics) > 0) {
          ?>

          <h4>Parent topics</h4>
          <ul>
            <?php
            foreach ($parentTopics as $parentTopic)
              echo '<li>' . bibliographie_topics_parse_name($parentTopic, array('linkProfile' => true)) . '</li>';
            ?>

          </ul>
          <?php
        }

        if (count(bibliographie_topics_get_subtopics($topic->topic_id, true)) > 0) {
          ?>
		<!-- open/close all subtopics-->
          <span style="float: right">
            <a href="javascript:;" onclick="bibliographie_topics_toggle_visiblity_of_all(true)">Open</a>
            <a href="javascript:;" onclick="bibliographie_topics_toggle_visiblity_of_all(false)">Close</a>
            all subtopics
          </span>
          
        <!-- subordinated topics-->
          <h4>Subordinated topics</h4>
          <div class="bibliographie_topics_topic_graph"><?php bibliographie_topics_traverse($topic->topic_id); ?></div>
          <?php
        }

        $tags = bibliographie_topics_get_tags($topic->topic_id);
        if (is_array($tags) and count($tags) > 0) {
          ?>


		<!-- tagcloud -->
          <h4>Publications have the following tagsSS</h4>
          <?php
          bibliographie_tags_print_cloud($tags, array('topic_id' => $topic->topic_id));
        }
        
		?>
		
		<!-- edit area -->
		<h4>Edit Topic 		
									<a id="hideeditbutton" onclick="		
									$('#editbox').hide();
									$('#hideeditbutton').hide();	
									$('#showeditbutton').show();	
									"
									><?php echo bibliographie_icon_get_big('bullet-toggle-minus') ?></a>
								<a id="showeditbutton" onclick="
									$('#editbox').show();
									$('#hideeditbutton').show();	
									$('#showeditbutton').hide();
								   "><?php echo bibliographie_icon_get_big('bullet-toggle-plus') ?></a>
		
		</h4>

		<div id="editbox">

			<?php
			// start of the editing area
			$done = false;
			$topic = null;

			if (!empty($_GET['topic_id']) and !in_array($_GET['topic_id'], bibliographie_topics_get_locked_topics())){
			  $topic = (array) bibliographie_topics_get_data($_GET['topic_id']);
			}
			
//check if history should be saved in here !
			if (is_array($topic)){
			  bibliographie_history_append_step('topics', 'Editing topic ' . $topic['name']);
			} else {
			  bibliographie_history_append_step('topics', 'Topic editor');
			}
			if ($_SERVER['REQUEST_METHOD'] == 'GET') {
			  if (is_array($topic)) {
				$_POST = $topic;
				$topics = bibliographie_topics_get_parent_topics($_GET['topic_id']);
				if (is_array($topics) and count($topics) > 0)
				  $_POST['topics'] = implode(',', $topics);
			  } else {
				$_POST['topics'] = 1;
			  }
			}
			if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			  $errors = array();
			  if (empty($_POST['name'])){
				$errors[] = 'You did not fill a name!';
			  }
			  if (!empty($_POST['url']) and !is_url($_POST['url'])){
				$errors[] = 'The URL you filled is not valid.';
			  }
			  $topics = csv2array($_POST['topics']);
			  if (count($errors) == 0) {
				if (is_array($topic)) {
				  if (bibliographie_topics_edit_topic($topic['topic_id'], $_POST['name'], $_POST['description'], $_POST['url'], $topics)) {
					echo '<p class="success">Topic has been edited.</p>';
					echo 'You can <a href="' . BIBLIOGRAPHIE_WEB_ROOT . '/topics/?task=topicEditor&amp;topic_id=' . $topic['topic_id'] . '">return to the editor</a> or view the <a href="' . BIBLIOGRAPHIE_WEB_ROOT . '/topics/?task=showTopic&amp;topic_id=' . $topic['topic_id'] . '">topic page</a>.';
					$done = true;
				  } else {
					echo '<p class="success">Topic could not be edited.</p>';
				  }
				}else {
				  if (bibliographie_topics_create_topic($_POST['name'], $_POST['description'], $_POST['url'], $topics)) {
					echo '<p class="success">Topic has been created.</p>';
					$done = true;
				  }
				  else
					echo '<p class="error">Topic could not be created!</p>';
				}
			  } else { 
				bibliographie_print_errors($errors);
			  }
			}
			//start part 2
			if (!$done) {
			  $prePopulateTopics = array();

			  /**
			   * Fill the prePropulateTopics array.
			   */
			  if (!empty($_POST['topics'])) {
				if (preg_match('~[0-9]+(\,[0-9]+)*~', $_POST['topics'])) {
				  $topics = csv2array($_POST['topics'], 'int');
				  foreach ($topics as $parentTopic) {
					$prePopulateTopics[] = array(
					  'id' => $parentTopic,
					  'name' => bibliographie_topics_parse_name($parentTopic)
					);
				  }
				}
			  }
			  ?>

			  <p class="notice">
				  Here you can edit the topic. Just fill at least the field for the name and hit save!</p>
			  <?php
			  if (is_array($topic)) {
				?>

				<form action="<?php echo BIBLIOGRAPHIE_WEB_ROOT . '/topics/?task=topicEditor&amp;topic_id=' . $topic['topic_id'] ?>" method="post">
				  <?php
				} else {
				  ?>

				  <form action="<?php echo BIBLIOGRAPHIE_WEB_ROOT . '/topics/?task=topicEditor' ?>" method="post">
					<?php
				  }
				  ?>

				  <div class="unit">
					<div style="float: right; width: 50%">
					  <label for="url" class="block">URL</label>
					  <input type="text" id="url" name="url" value="<?php echo htmlspecialchars($_POST['url']) ?>" style="width: 100%" />
					</div>

					<label for="name" class="block"><?php echo bibliographie_icon_get('asterisk-yellow') ?> Name</label>
					<input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name']) ?>" style="width: 45%" />

					<div id="similarNameContainer" class="bibliographie_similarity_container"></div>
				  </div>

				  <div class="unit">
					<label for="description" class="block">Description</label>
					<textarea id="description" name="description" rows="6" cols="40" style="width: 100%"><?php echo htmlspecialchars($_POST['description']) ?></textarea>
				  </div>

				  <div class="unit">
					<label for="topics" class="block">Parent topics</label>
					<div id="topicsContainer" style="background: #fff; border: 1px solid #aaa; color: #000; float: right; font-size: 0.8em; padding: 5px; width: 45%;"><em>Search for a topic in the left container!</em></div>
					<input type="text" id="topics" name="topics" style="width: 100%" value="<?php echo htmlspecialchars($_POST['topics']) ?>" />
					<br style="clear: both" />
				  </div>

				  <div class="submit">
					<input type="submit" value="save" />
				  </div>
				</form>

				<script type="text/javascript">
				  /* <![CDATA[ */
				  var topic_id = <?php
			  if (is_array($topic))
				echo $topic['topic_id'];
			  else
				echo 0;
			  ?>;

				  $(function() {
					bibliographie_topics_input_tokenized('topics', 'topicsContainer', <?php echo json_encode($prePopulateTopics) ?>);

					$('#name').bind('mouseup keyup', function() {
					  delayRequest('bibliographie_topics_check_name', Array($('#name').val(), topic_id));
					});

					$('#content input, #content textarea').charmap();
				  });
				  /* ]]> */
				</script>
				<?php
				bibliographie_charmap_print_charmap();
			  }
			
			
			?>
			
			
		</div>
		
		
		
		
		
		<?php
  
      }
      break;

    case 'showPublications':
      $topic = bibliographie_topics_get_data($_GET['topic_id']);
      if ($topic) {
        bibliographie_history_append_step('topics', 'Showing publications of topic ' . $topic->name . ' (page ' . ((int) $_GET['page']) . ')');

        $includeSubtopics = '';
        $title = '';
        if ($_GET['includeSubtopics'] == 1) {
          $includeSubtopics = '&includeSubtopics=1';
          $title = ' and subtopics';
        }
        ?>

        <h3>Publications assigned to <a href="<?php echo BIBLIOGRAPHIE_WEB_ROOT ?>/topics/?task=showTopic&amp;topic_id=<?php echo $topic->topic_id ?>"><?php echo htmlspecialchars($topic->name) ?></a><?php echo $title ?></h3>
        <?php
        echo bibliographie_publications_print_list(
          bibliographie_topics_get_publications($topic->topic_id, (bool) $_GET['includeSubtopics']), BIBLIOGRAPHIE_WEB_ROOT . '/topics/?task=showPublications&topic_id=' . ((int) $_GET['topic_id']) . $includeSubtopics
        );
      }
      break;

    default:
    case 'showGraph':
      bibliographie_history_append_step('topics', 'Showing topic graph');

      $bibliographie_topics_graph_depth = (int) 1;

      $top = (int) 1;
      $bibliographie_title = 'Topic graph';
      $topic = bibliographie_topics_get_data($_GET['topic_id']);
      if (is_object($topic)) {
        $top = (int) $topic->topic_id;
        $bibliographie_title = 'Topic subgraph for <a href="' . BIBLIOGRAPHIE_WEB_ROOT . '/topics/?task=showTopic&amp;topic_id=' . $topic->topic_id . '">' . htmlspecialchars($topic->name) . '</a></em>';
      }
      ?>

      <span style="float: right">
        <a href="javascript:;" onclick="bibliographie_topics_toggle_visiblity_of_all(true)"><?php echo bibliographie_icon_get('bullet-toggle-plus') ?> Open</a>
        <a href="javascript:;" onclick="bibliographie_topics_toggle_visiblity_of_all(false)"><?php echo bibliographie_icon_get('bullet-toggle-minus') ?> Close</a>
        all subtopics
        <a href="javascript:;" onclick="bibliographie_topics_toggle_visibility_of_topicID()">show Topic IDs</a>

      </span>

      <h3><?php echo $bibliographie_title ?></h3>

      <div class="bibliographie_topics_topic_graph"><?php echo bibliographie_topics_traverse($top) ?></div>
      <p class="notice">Depth of graph: <?php echo $bibliographie_topics_graph_depth ?></p>
      <?php
      break;
  }

  require BIBLIOGRAPHIE_ROOT_PATH . '/close.php';


