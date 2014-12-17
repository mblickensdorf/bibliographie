<?php
require '../init.php';
?>

<h2>Publications</h2>
<?php
// depending on the $_GET['task'] variable, different contents are displayed


switch ($_GET['task']) {
	//case
	case 'deleteAttachment':
		bibliographie_history_append_step('attachments', 'Delete attachment', false);
		echo '<h3>Delete attachment</h3>';
		$attachment = bibliographie_attachments_get_data($_GET['att_id']);
		if (is_object($attachment)) {
			if (bibliographie_attachments_delete($attachment->att_id))
				echo '<p class="success">Attachment was deleted!</p>';
			else
				echo '<p class="error">An error occurred!</p>';
		}else
			echo '<p class="error">Attachment was not found!</p>';
		break;
	//case
	case 'deletePublication':
		bibliographie_history_append_step('publications', 'Delete publication', false);
		echo '<h3>Delete publication</h3>';
		$publication = bibliographie_publications_get_data($_GET['pub_id']);
		if (is_object($publication)) {
			$notes = bibliographie_publications_get_notes($publication->pub_id);

			if (count($notes) == 0) {
				if (bibliographie_publications_delete_publication($publication->pub_id))
					echo '<p class="success">Publication was deleted!</p>';
				else
					echo '<p class="error">An error occurred!</p>';
			}else {
				echo '<p class="error">Publication cannot be deleted since users have taken notes on this publication!</p><p class="notice">If you want to delete this publication anyway contact your administrator!</p>';
				echo 'This is a list of users that have taken notes on this publication.<ul>';
				foreach ($notes->fetchAll(PDO::FETCH_OBJ) as $note)
					echo '<li><strong>' . bibliographie_user_get_name($note->user_id) . '</strong></li>';
				echo '</ul>You can ask them to delete their notes and delete the publication afterwards.';
			}
		}else
			echo '<p class="error">Publication was not found!</p>';
		break;
	//case
	case 'batchOperations':
		$publications = bibliographie_publications_get_cached_list($_GET['list']);

		if (is_array($publications) and count($publications) > 0) {
			if ($_GET['category'] == 'topics') {
				if (!empty($_POST['topics']) and is_csv($_POST['topics'], 'int')) {
					$topics = csv2array($_POST['topics'], 'int');

					if ($_POST['addTopics'] == 'Add topics') {
						echo '<p class="notice">Adding topics to publications...</p>';
						echo '<ul>';
						foreach ($topics as $topic) {
							$topic = bibliographie_topics_get_data($topic);

							if (!is_object($topic))
								continue;

							$topicFamily = bibliographie_topics_get_parent_topics($topic->topic_id);
							$topicFamily[] = $topic->topic_id;
							if (count(array_intersect($topicFamily, bibliographie_topics_get_locked_topics()))) {
								echo '<li>' . bibliographie_icon_get('error') . ' ' . bibliographie_topics_parse_name($topic->topic_id, array('linkProfile' => true)) . ' is in the list of locked topics. No changes were committed to this topic!</li>';
								continue;
							}

							echo '<li>';
							echo 'Adding topic ' . bibliographie_topics_parse_name($topic->topic_id, array('linkProfile' => true)) . ' ... ';
							$result = bibliographie_publications_add_topic($publications, $topic->topic_id);
							if (is_array($result)) {
								echo bibliographie_icon_get('tick') . ' Success!<br />'
								. '<em>' . count($result['publicationsAdded']) . ' publications were added. ' . count(array_diff($publications, $result['publicationsToAdd'])) . ' had this topic already.</em>';

								if (count($result['publicationsAdded']) != count($result['publicationsToAdd']))
									echo '<br /><span class="error">' . (count($result['publicationsToAdd']) - count($result['publicationsAdded'])) . ' could not be added.</span>';
							}else
								echo bibliographie_icon_get('cross') . ' An error occurred!';

							echo '</li>';
						}
						echo '</ul>';
					}elseif ($_POST['removeTopics'] == 'Remove topics') {
						echo '<p class="notice">Removing topics from publications...</p>';
						echo '<ul>';
						foreach ($topics as $topic) {
							$topic = bibliographie_topics_get_data($topic);

							if (!is_object($topic))
								continue;

							$topicFamily = bibliographie_topics_get_parent_topics($topic->topic_id);
							$topicFamily[] = $topic->topic_id;
							if (count(array_intersect($topicFamily, bibliographie_topics_get_locked_topics()))) {
								echo '<li>' . bibliographie_icon_get('error') . ' ' . bibliographie_topics_parse_name($topic->topic_id, array('linkProfile' => true)) . ' is in the list of locked topics. No changes were committed to this topic!</li>';
								continue;
							}

							echo '<li>';
							echo 'Removing topic ' . bibliographie_topics_parse_name($topic->topic_id, array('linkProfile' => true)) . ' ... ';
							$result = bibliographie_publications_remove_topic($publications, $topic->topic_id);
							if (is_array($result)) {
								echo bibliographie_icon_get('tick') . ' Success!<br />'
								. '<em>Topic was removed from the publications.</em>';
							}else
								echo bibliographie_icon_get('cross') . ' An error occurred!';

							echo '</li>';
						}
						echo '</ul>';
					}
				}else
					echo '<p class="error">You did not supply a list of topics to work with!</p>';
			}elseif ($_GET['category'] == 'tags') {
				if (!empty($_POST['tags']) and is_csv($_POST['tags'], 'int')) {
					$tags = csv2array($_POST['tags'], 'int');

					if ($_POST['addTags'] == 'Add tags') {
						echo '<p class="notice">Adding tags to publications...</p>';
						echo '<ul>';
						foreach ($tags as $tag) {
							$tag = bibliographie_tags_get_data($tag);

							if (!is_object($tag))
								continue;

							echo 'Adding tag ' . bibliographie_tags_parse_tag($tag->tag_id, array('linkProfile' => true)) . ' ... ';
							$result = bibliographie_publications_add_tag($publications, $tag->tag_id);
							if (is_array($result)) {
								echo bibliographie_icon_get('tick') . ' Success!<br />'
								. '<em>' . count($result['publicationsAdded']) . ' publications were added. ' . count(array_diff($publications, $result['publicationsToAdd'])) . ' had this tag already.</em>';

								if (count($result['publicationsAdded']) != count($result['publicationsToAdd']))
									echo '<br /><span class="error">' . (count($result['publicationsToAdd']) - count($result['publicationsAdded'])) . ' could not be added.</span>';
							}else
								echo bibliographie_icon_get('cross') . ' An error occurred!';
						}
						echo '</ul>';
					}elseif ($_POST['removeTags'] == 'Remove tags') {
						echo '<p class="notice">Removing tags from publications...</p>';
						echo '<ul>';
						foreach ($tags as $tag) {
							$tag = bibliographie_tags_get_data($tag);

							if (!is_object($tag))
								continue;

							echo '<li>';
							echo 'Removing tag ' . bibliographie_tags_parse_tag($tag->tag_id, array('linkProfile' => true)) . ' ... ';
							$result = bibliographie_publications_remove_tag($publications, $tag->tag_id);
							if (is_array($result)) {
								echo bibliographie_icon_get('tick') . ' Success!<br />'
								. '<em>Tag was removed from the publications.</em>';
							}else
								echo bibliographie_icon_get('cross') . ' An error occurred!';

							echo '</li>';
						}
						echo '</ul>';
					}
				}
			}
			?>

			<form action="<?php echo BIBLIOGRAPHIE_WEB_ROOT ?>/publications/?task=batchOperations&amp;list=<?php echo $_GET['list'] ?>&amp;category=topics" method="post">
				<h3><?php echo bibliographie_icon_get('folder') ?> Topics</h3>
				<div class="unit">
					<label for="topics" class="block">Topics</label>
					<div id="topicsContainer" style="background: #fff; border: 1px solid #aaa; color: #000; float: right; font-size: 0.8em; max-height: 200px; overflow-y: scroll; padding: 5px; width: 45%;"><em>Search for a topic in the left container!</em></div>

					<input type="text" id="topics" name="topics" style="width: 100%" value="<?php echo htmlspecialchars($_POST['topics']) ?>" />

					<br style="clear: both" />

					<em>Please select the topics that you want to add to or remove from the publications.</em>
				</div>

				<div class="submit">
					<input type="submit" name="addTopics" value="Add topics" />
					<input type="submit" name="removeTopics" value="Remove topics" />
				</div>
			</form>

			<form action="<?php echo BIBLIOGRAPHIE_WEB_ROOT ?>/publications/?task=batchOperations&amp;list=<?php echo $_GET['list'] ?>&amp;category=tags" method="post">
				<h3><?php echo bibliographie_icon_get('tag-blue') ?> Tags</h3>
				<div class="unit">
					<label for="tags" class="block">Tags</label>
					<em style="float: right; text-align: right;">
						<a href="javascript:;" onclick="bibliographie_tags_create_tag()"><span class="silk-icon silk-icon-tag-blue-add"></span> Add new tag</a><br />
						<span id="tags_tagNotExisting"></em>
					</em>
					<input type="text" id="tags" name="tags" style="width: 100%" value="<?php echo htmlspecialchars($_POST['tags']) ?>" />
					<br style="clear: both;" />
					<em>Please select tags that you want to tag the publications with or remove from the publications.</em>
				</div>

				<div class="submit">
					<input type="submit" name="addTags" value="Add tags" />
					<input type="submit" name="removeTags" value="Remove tags" />
				</div>
			</form>

			<h3><?php echo bibliographie_icon_get('page-white-stack') ?> Publications that will be affected</h3>
			<?php
			echo bibliographie_publications_print_list(
				$publications, BIBLIOGRAPHIE_WEB_ROOT . '/publications/?task=batchOperations&amp;list=' . $_GET['list'], array(
				'orderBy' => 'title'
				)
			);
			bibliographie_charmap_print_charmap();
			?>

			<script type="text/javascript">
				/* <![CDATA[ */
				$(function () {
					bibliographie_topics_input_tokenized('topics', 'topicsContainer', <?php echo json_encode(bibliographie_topics_populate_input($_POST['topics'])) ?>);
					bibliographie_tags_input_tokenized('tags', <?php echo json_encode(bibliographie_tags_populate_input($_POST['tags'])) ?>);

					$('#topics').charmap();
				})
				/* ]]> */
			</script>
			<?php
			bibliographie_history_append_step('publications', 'Batch operations (' . count($publications) . ' publications)');
		}else
			echo '<h3 class="error">List was empty</h3><p>Sorry, but the list you provided was empty!</p>';
		break;
	//case
	case 'showContainer':
		if (in_array($_GET['type'], array('journal', 'book'))) {
			bibliographie_history_append_step('publications', 'Showing ' . $_GET['type'] . ' "' . htmlspecialchars($_GET['container']) . '"');
			$fields = array(
				'journal',
				'volume'
			);
			if ($_GET['type'] == 'book')
				$fields = array(
					'booktitle',
					'number'
				);

			$containers = DB::getInstance()->prepare('SELECT
	`year`,
	`journal`,
	`volume`,
	`booktitle`,
	`number`,
	COUNT(*) AS `count`
FROM
	`' . BIBLIOGRAPHIE_PREFIX . 'publication`
WHERE
	`' . $fields[0] . '` = :container
GROUP BY
	`' . $fields[1] . '`
ORDER BY
	`year`,
	`' . $fields[1] . '`');
			$containers->execute(array(
				'container' => $_GET['container']
			));

			if ($containers->rowCount() > 0) {
				$result = $containers->fetchAll(PDO::FETCH_ASSOC);

				echo '<h3>Chronology of ' . htmlspecialchars($_GET['container']) . '</h3>';
				echo '<table class="dataContainer">';
				echo '<tr><th></th><th>' . htmlspecialchars($fields[0]) . '</th><th>Year & ' . htmlspecialchars($fields[1]) . '</th><th># of articles</th></tr>';

				foreach ($result as $container) {
					echo '<tr>'
					. '<td><a href="' . BIBLIOGRAPHIE_WEB_ROOT . '/publications/?task=showContainerPiece&amp;type=' . htmlspecialchars($_GET['type']) . '&amp;container=' . htmlspecialchars($container[$fields[0]]) . '&amp;year=' . ((int) $container['year']) . '&amp;piece=' . htmlspecialchars($container[$fields[1]]) . '">' . bibliographie_icon_get('page-white-stack', 'Show publications') . '</a></td>'
					. '<td>' . htmlspecialchars($container[$fields[0]]) . '</td>'
					. '<td>' . $container['year'] . ' ' . $container[$fields[1]] . '</td>'
					. '<td>' . $container['count'] . ' article(s)</td>'
					. '</tr>';
				}
				echo '</table>';
			}
		}
		break;
	//case
	case 'showContainerPiece':
		if (in_array($_GET['type'], array('journal', 'book'))) {
			bibliographie_history_append_step('publications', 'Showing ' . $_GET['type'] . ' "' . htmlspecialchars($_GET['container']) . '"/' . ((int) $_GET['year']) . '/' . ((int) $_GET['piece']));
			$fields = array(
				'journal',
				'volume'
			);
			if ($_GET['type'] == 'book')
				$fields = array(
					'booktitle',
					'number'
				);

			$publications = DB::getInstance()->prepare('SELECT
	`pub_id`
FROM
	`' . BIBLIOGRAPHIE_PREFIX . 'publication`
WHERE
	`' . $fields[0] . '` = :container AND
	`year` = :year AND
	`' . $fields[1] . '` = :piece');

			$publications->execute(array(
				'container' => $_GET['container'],
				'year' => (int) $_GET['year'],
				'piece' => $_GET['piece']
			));

			$publications = $publications->fetchAll(PDO::FETCH_COLUMN, 0);

			if (count($publications) > 0) {
				?>

				<h3>Publications in <a href="<?php echo BIBLIOGRAPHIE_WEB_ROOT ?>/publications/?task=showContainer&amp;type=<?php echo htmlspecialchars($_GET['type']) ?>&amp;container=<?php echo htmlspecialchars($_GET['container']) ?>"><?php echo htmlspecialchars($_GET['container']) ?></a>, <?php echo ((int) $_GET['year']) . ' ' . htmlspecialchars($_GET[$field[1]]) ?></h3>
				<?php
				echo bibliographie_publications_print_list(
					$publications, BIBLIOGRAPHIE_WEB_ROOT . '/publications/?task=showContainerPiece&amp;type=' . htmlspecialchars($_GET['type']) . '&amp;container=' . htmlspecialchars($_GET['container']) . '&amp;year=' . ((int) $_GET['year']) . '&amp;piece=' . htmlspecialchars($_GET['piece']), array(
					'orderBy' => 'title'
					)
				);
			}
		}
		break;
	//case
	case 'checkData':
		/**
		 * Unset yet checked prefetched data.
		 */
		unset($_SESSION['publication_prefetchedData_checked']);
		bibliographie_history_append_step('publications', 'Precheck data from external source');
		?>

		<h3>Check fetched data</h3>
		<p class="notice">Please precheck all of the parsed authors now before moving to creating them in the publication editor!</p>
		<?php
		if (is_array($_SESSION['publication_prefetchedData_unchecked'])) {
			$searchPersons = array();

			/**
			 * Loop for entries...
			 */
			foreach ($_SESSION['publication_prefetchedData_unchecked'] as $entryID => $entry) {
				?>

				<div id="bibliographie_checkData_entry_<?php echo $entryID ?>" class="bibliographie_checkData_entry">
					<em class="bibliographie_checkData_pubType"><?php echo $entry['pub_type'] ?></em>
					<strong><?php echo $entry['title'] ?></strong>

					<div id="bibliographie_checkData_approvalResult_<?php echo $entryID ?>"></div>

					<div class="bibliographie_checkData_persons">
						<span style="float: right; font-size: 0.8em; text-align: right;">
							<a href="javascript:;" onclick="bibliographie_publications_check_data_approve_entry(<?php echo $entryID ?>)">Approve entry <?php echo bibliographie_icon_get('tick') ?> </a><br />
							<a href="javascript:;" onclick="bibliographie_publications_check_data_approve_all(<?php echo $entryID ?>)">Approve all persons and entry <?php echo bibliographie_icon_get('tick') ?></a><br />
							<a href="javascript:;" onclick="$('#bibliographie_checkData_entry_<?php echo $entryID ?>').hide('slow', function() {$(this).remove()})">Remove entry <?php echo bibliographie_icon_get('cross') ?></a>
						</span>
				<?php
				/**
				 * Loop for persons... Authors and editors...
				 */
				$persons = false;
				foreach (array('author', 'editor') as $role) {
					if (count($entry[$role]) > 0) {
						$persons = true;

						foreach ($entry[$role] as $personID => $person) {
							/**
							 * Put the person in the array that is needed for js functionality...
							 */
							$searchPersons[$entryID][$role][$personID] = array(
								'htmlID' => $entryID . '_' . $role . '_' . $personID,
								'role' => $role,
								'entryID' => $entryID,
								'personID' => $personID,
								'name' => $person['first'] . ' ' . $person['von'] . ' ' . $person['last'] . ' ' . $person['jr'],
								'first' => $person['first'],
								'von' => $person['von'],
								'last' => $person['last'],
								'jr' => $person['jr'],
								'approved' => false
							);

							if (!empty($person['jr']))
								$person['jr'] = ' ' . $person['jr'];
							?>

									<div id="bibliographie_checkData_person_<?php echo $entryID . '_' . $role . '_' . $personID ?>" style="margin-top: 10px;">
									<?php echo $role ?> #<?php echo ((string) $personID + 1) ?>:
									<?php echo $person['von'] . ' <strong>' . $person['last'] . '</strong>' . $person['jr'] . ', ' . $person['first'] ?>
										<div id="bibliographie_checkData_personResult_<?php echo $entryID . '_' . $role . '_' . $personID ?>"><img src="<?php echo BIBLIOGRAPHIE_ROOT_PATH ?>/resources/images/loading.gif" alt="pending" /></div>
									</div>
									<?php
								}
							}
						}

						/**
						 * Tell if no persons were parsed...
						 */
						if (!$persons)
							echo '<p class="error">No persons could be parsed for this entry!</p>';
						?>

					</div>
				</div>
						<?php
					}
					?>

			<div class="submit"><button onclick="window.location = bibliographie_web_root+'/publications/?task=publicationEditor&amp;useFetchedData=1';">Go to publication editor</button></div>

			<script type="text/javascript">
				/* <![CDATA[ */
				var bibliographie_checkData_searchPersons = <?php echo json_encode($searchPersons) ?>;

				$(function () {
					$.each(bibliographie_checkData_searchPersons, function (entryID, entries) {
						$.each(entries, function (role, persons) {
							$.each(persons, function (personID, person){
								bibliographie_publications_search_author_for_approval(role, person);
							})
						});
					});
				});
				/* ]]> */
			</script>
			<?php
			break;
		}else
			echo '<p class="error">You did not fetch any data yet! You may want to do so now!</p>';
	//case
	case 'fetchData':
		bibliographie_history_append_step('publications', 'Fetch data from external source');
		unset($_SESSION['publication_prefetchedData_checked']);
		unset($_SESSION['publication_prefetchedData_unchecked']);
		?>

		<h3>Fetch data for publication creation</h3>
		<div id="fetchData_sourceSelect">
			<label for="source" class="block"><?php echo bibliographie_icon_get('page-white-get') ?> Select the source of which you want to import from!</label>
			<select id="source" name="source" style="width: 50%;">
				<option value="direct">Direct input</option>
				<option value="remote">Remote file</option>
				<option value="pubmed">PubMed</option>
		<?php
		if (BIBLIOGRAPHIE_ISBNDB_KEY != '')
			echo '<option value="isbndb">ISBNDB.com</option>';
		?>

			</select>
			<button onclick="bibliographie_publications_fetch_data_proceed({'source': $('#source').val(), 'step': '1'})">Select!</button>
		</div>
		<p><hr /></p>
		<div id="fetchData_container"></div>
		<script type="text/javascript">
			/* <![CDATA[ */
			$('#source').on('change select', function () {
				bibliographie_publications_fetch_data_proceed({'source': $('#source').val(), 'step': '1'})
			});
			$(function () {
				bibliographie_publications_fetch_data_proceed({'source': 'direct', 'step': '1'});
			})
			/* ]]> */
		</script>
		<?php
		break;
	//case
	case 'publicationEditor':
		$bibliographie_title = 'Publication editor';
		$done = false;

		$publication = null;
		if (!empty($_GET['pub_id']))
			$publication = (array) bibliographie_publications_get_data($_GET['pub_id']);

		if (is_array($publication))
			bibliographie_history_append_step('publications', 'Editing publication ' . $publication['title']);
		else
			bibliographie_history_append_step('publications', 'Creating publication');

		if ($_GET['skipEntry'] == '1')
			array_shift($_SESSION['publication_prefetchedData_checked']);

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$errors = array();

			if (in_array($_POST['pub_type'], $bibliographie_publication_types)) {
				foreach ($bibliographie_publication_fields[mb_strtolower($_POST['pub_type'])][0] as $requiredField) {
					if (mb_strpos($requiredField, ',') !== false) {
						$fields = explode(',', $requiredField);
						if (empty($_POST[$fields[0]]) and empty($_POST[$fields[1]]))
							echo '<p class="notice">You should have filled either ' . $fields[0] . ' or ' . $fields[1] . '!</p>';
					}elseif (empty($_POST[$requiredField]))
						echo '<p class="notice">You should have filled ' . $requiredField . '!</p>';
				}

				$author = csv2array($_POST['author'], 'int');
				$editor = csv2array($_POST['editor'], 'int');
				$topics = csv2array($_POST['topics'], 'int');
				$tags = csv2array($_POST['tags'], 'int');

				if (count($errors) == 0) {
					if (is_array($publication)) {
						echo '<h3>Updating publication...</h3>';

						$data = bibliographie_publications_edit_publication($publication['pub_id'], $_POST['pub_type'], $author, $editor, $_POST['title'], $_POST['month'], $_POST['year'], $_POST['booktitle'], $_POST['chapter'], $_POST['series'], $_POST['journal'], $_POST['volume'], $_POST['number'], $_POST['edition'], $_POST['publisher'], $_POST['location'], $_POST['howpublished'], $_POST['organization'], $_POST['institution'], $_POST['school'], $_POST['address'], $_POST['pages'], $_POST['note'], $_POST['abstract'], $_POST['userfields'], $_POST['bibtex_id'], $_POST['isbn'], $_POST['issn'], $_POST['doi'], $_POST['url'], $topics, $tags);

						if (is_array($data)) {
							echo '<p class="success">Publication has been edited!</p>';
							echo 'You can <a href="' . BIBLIOGRAPHIE_WEB_ROOT . '/publications/?task=showPublication&amp;pub_id=' . ((int) $data['pub_id']) . '">view the created publication</a> or you can proceed by <a href="' . BIBLIOGRAPHIE_WEB_ROOT . '/publications/?task=publicationEditor">creating another</a> publication.';
							$done = true;
						}else
							echo '<p class="error">An error occurred!</p>';
					}else {
						echo '<h3>Creating publication...</h3>';

						$data = bibliographie_publications_create_publication($_POST['pub_type'], $author, $editor, $_POST['title'], $_POST['month'], $_POST['year'], $_POST['booktitle'], $_POST['chapter'], $_POST['series'], $_POST['journal'], $_POST['volume'], $_POST['number'], $_POST['edition'], $_POST['publisher'], $_POST['location'], $_POST['howpublished'], $_POST['organization'], $_POST['institution'], $_POST['school'], $_POST['address'], $_POST['pages'], $_POST['note'], $_POST['abstract'], $_POST['userfields'], $_POST['bibtex_id'], $_POST['isbn'], $_POST['issn'], $_POST['doi'], $_POST['url'], $topics, $tags);

						if (is_array($data)) {
							echo '<p class="success">Publication has been created!</p>';
							echo 'You can <a href="' . BIBLIOGRAPHIE_WEB_ROOT . '/publications/?task=showPublication&amp;pub_id=' . ((int) $data['pub_id']) . '">view the created publication</a> or you can proceed by <a href="' . BIBLIOGRAPHIE_WEB_ROOT . '/publications/?task=publicationEditor">creating another</a> publication.';

							if ($_GET['useFetchedData'] == '1') {
								array_shift($_SESSION['publication_prefetchedData_checked']);
								if (count($_SESSION['publication_prefetchedData_checked']) > 0)
									echo '<br /><br /><a href="' . BIBLIOGRAPHIE_WEB_ROOT . '/publications/?task=publicationEditor&amp;useFetchedData=1">' . bibliographie_icon_get('page-white-go') . ' Proceed publication creation with fetched data.</a>';
								else
									echo '<br /><br /><em>' . bibliographie_icon_get('page-white-go') . ' Prefetched data queue is now empty!</em>';
							}

							$done = true;
						}else
							echo '<p class="error">Something went wrong. Publication could not be created!</p>';
					}
				}else
					bibliographie_print_errors($errors);
			}
		}

		if (!$done) {
			/**
			 * Initialize arrays for pre populating specific fields in the form.
			 */
			$prePopulateAuthor = array();
			$prePopulateEditor = array();
			$prePopulateTags = array();
			$prePopulateTopics = array();

			/**
			 * If requested parse existing publication and prefill the form with that.
			 */
			$usingFetchedData = false;
			if ($_SERVER['REQUEST_METHOD'] == 'GET') {
				if ($_GET['useFetchedData'] == '1' and count($_SESSION['publication_prefetchedData_checked']) > 0) {
					$_POST = reset($_SESSION['publication_prefetchedData_checked']);
					if (count($_POST['checked_author']) == count($_POST['author']) and count($_POST['checked_editor']) == count($_POST['editor'])) {
						if (is_array($_POST['checked_author']))
							$_POST['author'] = array2csv($_POST['checked_author']);
						else
							$_POST['author'] = '';

						if (is_array($_POST['checked_editor']))
							$_POST['editor'] = array2csv($_POST['checked_editor']);
						else
							$_POST['editor'] = '';

						if (is_array($_POST['tags']))
							$_POST['tags'] = array2csv($_POST['tags']);

						$usingFetchedData = true;
					}else {
						echo '<p class="error">There was an error with the prefetched authors!</p>';
						$_POST = array();
					}
				} elseif (is_array($publication)) {
					$_POST = $publication;

					$authors = bibliographie_publications_get_authors($_GET['pub_id']);
					if (is_array($authors) and count($authors) > 0)
						$_POST['author'] = array2csv($authors);

					$editors = bibliographie_publications_get_editors($_GET['pub_id']);
					if (is_array($editors) and count($editors) > 0)
						$_POST['editor'] = array2csv($editors);

					$tags = bibliographie_publications_get_tags($_GET['pub_id']);
					if (is_array($tags) and count($tags) > 0)
						$_POST['tags'] = array2csv($tags);

					$topics = bibliographie_publications_get_topics($_GET['pub_id']);
					if (is_array($topics) and count($topics) > 0)
						$_POST['topics'] = array2csv($topics);
				}
			}

			/**
			 * Fill the prePopluate arrays.
			 */
			$prePopulateAuthor = bibliographie_authors_populate_input($_POST['author']);
			$prePopulateEditor = bibliographie_authors_populate_input($_POST['editor']);
			$prePopulateTags = bibliographie_tags_populate_input($_POST['tags']);
			$prePopulateTopics = bibliographie_topics_populate_input($_POST['topics']);
			?>

			<h3>Publication editor</h3>
			<?php
			if (count($_SESSION['publication_prefetchedData_checked']) > 0 and $_GET['useFetchedData'] != '1') {
				?>

				<p class="notice"><?php echo bibliographie_icon_get('page-white-go') ?> You have <?php echo count($_SESSION['publication_prefetchedData_checked']) ?> entries in the fetched data queue. You might want to <a href="<?php echo BIBLIOGRAPHIE_WEB_ROOT ?>/publications/?task=publicationEditor&amp;useFetchedData=1"> use the fetched data</a>.</p>
				<?php
			}

			if ($usingFetchedData) {
				?>

				<p class="notice"><?php echo bibliographie_icon_get('page-white-go') ?> Using the first of <?php echo count($_SESSION['publication_prefetchedData_checked']) ?> entries in the fetched data queue. <a href="<?php echo BIBLIOGRAPHIE_WEB_ROOT ?>/publications/?task=publicationEditor&amp;useFetchedData=1&amp;skipEntry=1">Skip this one.</a></a></p>
				<form action="<?php echo BIBLIOGRAPHIE_WEB_ROOT ?>/publications/?task=publicationEditor&amp;useFetchedData=1" method="post">
				<?php
			} elseif (is_array($publication)) {
				?>

					<form action="<?php echo BIBLIOGRAPHIE_WEB_ROOT ?>/publications/?task=publicationEditor&amp;pub_id=<?php echo ((int) $publication['pub_id']) ?>" method="post" onsubmit="return bibliographie_publications_check_required_fields();">
				<?php
			} else {
				?>

						<form action="<?php echo BIBLIOGRAPHIE_WEB_ROOT ?>/publications/?task=publicationEditor" method="post" onsubmit="return bibliographie_publications_check_required_fields();">
					<?php
				}
				?>

						<div class="unit"><h4>General data</h4>
							<label for="pub_type" class="block">Publication type</label>
							<select id="pub_type" name="pub_type" style="width: 100%" tabindex="1">
			<?php
			foreach ($bibliographie_publication_types as $type) {
				echo '<option value="' . $type . '"';
				if ($type == $_POST['pub_type'])
					echo ' selected="selected"';
				echo '>' . $type . '</option>';
			}
			?>

							</select>

							<p id="authorOrEditorNotice" class="notice" style="display: none;"><span class="silk-icon silk-icon-asterisk-yellow"></span> Either you have to fill an author or an editor!</p>

							<div id="authorContainer">
								<label for="author" class="block">Author(s)</label>
								<em style="float: right"><a href="javascript:;" onclick="bibliographie_publications_create_person_form('author')"><span class="silk-icon silk-icon-user-add"></span> Add new author</a></em>
								<input type="text" id="author" name="author" style="width: 100%" value="<?php echo htmlspecialchars($_POST['author']) ?>" tabindex="2" />
							</div>

							<div id="editorContainer">
								<label for="editor" class="block">Editor(s)</label>
								<em style="float: right"><a href="javascript:;" onclick="bibliographie_publications_create_person_form('editor')"><span class="silk-icon silk-icon-user-add"></span> Add new editor</a></em>
								<input type="text" id="editor" name="editor" style="width: 100%" value="<?php echo htmlspecialchars($_POST['editor']) ?>" tabindex="3" />
							</div>

							<label for="title" class="block">Title</label>
							<input type="text" id="title" name="title" style="width: 100%" value="<?php echo htmlspecialchars($_POST['title']) ?>" class="bibtex" tabindex="4" />

							<div id="similarTitleContainer" class="bibliographie_similarity_container"></div>

							<label for="bibtex_id" class="block">BibTex cite ID</label>
							<input id="bibtex_id" name="bibtex_id" style="width: 100%" value="<?php echo htmlspecialchars($_POST['bibtex_id']) ?>" class="" tabindex="27" />

							<div style="float: right; width: 50%">
								<label for="month" class="block">Month</label>
								<select id="month" name="month" style="width: 100%" class="bibtex" tabindex="5">
									<option value=""></option>
			<?php
			foreach ($bibliographie_publication_months as $month) {
				echo '<option value="' . $month . '"';
				if ($month == $_POST['month'])
					echo ' selected="selected"';
				echo '>' . $month . '</option>';
			}
			?>

								</select>
							</div>

							<label for="year" class="block">Year</label>
							<input type="text" id="year" name="year" style="width: 45%" value="<?php echo htmlspecialchars($_POST['year']) ?>" class="bibtex" tabindex="6" />
						</div>

						<div class="unit"><h4>Topics & tags</h4>
							<label for="topics" class="block">Topics</label>
							<div id="topicsContainer" style="background: #fff; border: 1px solid #aaa; color: #000; float: right; font-size: 0.8em; max-height: 200px; overflow-y: scroll; padding: 5px; width: 45%;"><em>Search for a topic in the left container!</em></div>
							<input type="text" id="topics" name="topics" style="width: 100%" value="<?php echo htmlspecialchars($_POST['topics']) ?>" tabindex="7" />
							<br style="clear: both" />

							<label for="tags" class="block">Tags</label>
							<em style="float: right; text-align: right;">
								<a href="javascript:;" onclick="bibliographie_tags_create_tag()"><span class="silk-icon silk-icon-tag-blue-add"></span> Add new tag</a><br />
								<span id="tags_tagNotExisting"></em>
							</em>
							<input type="text" id="tags" name="tags" style="width: 100%" value="<?php echo htmlspecialchars($_POST['tags']) ?>" tabindex="8" />
							<br style="clear: both;" />
						</div>

						<div class="unit bibtex"><h4>Association</h4>
							<label for="booktitle" class="block">Booktitle</label>
							<input type="text" id="booktitle" name="booktitle" style="width: 100%" value="<?php echo htmlspecialchars($_POST['booktitle']) ?>" class="bibtex" tabindex="9" />

							<label for="chapter" class="block">Chapter</label>
							<input type="text" id="chapter" name="chapter" style="width: 100%" value="<?php echo htmlspecialchars($_POST['chapter']) ?>" class="bibtex" tabindex="10" />

							<label for="series" class="block">Series</label>
							<input type="text" id="series" name="series" style="width: 100%" value="<?php echo htmlspecialchars($_POST['series']) ?>" class="bibtex" tabindex="11" />

							<label for="journal" class="block">Journal</label>
							<input type="text" id="journal" name="journal" style="width: 100%" value="<?php echo htmlspecialchars($_POST['journal']) ?>" class="bibtex" tabindex="12" />

							<label for="volume" class="block">Volume</label>
							<input type="text" id="volume" name="volume" style="width: 100%" value="<?php echo htmlspecialchars($_POST['volume']) ?>" class="bibtex" tabindex="13" />

							<label for="number" class="block">Number</label>
							<input type="text" id="number" name="number" style="width: 100%" value="<?php echo htmlspecialchars($_POST['number']) ?>" class="bibtex" tabindex="14" />

							<label for="edition" class="block">Edition</label>
							<input type="text" id="edition" name="edition" style="width: 100%" value="<?php echo htmlspecialchars($_POST['edition']) ?>" class="bibtex" tabindex="15" />
						</div>

						<div class="unit bibtex"><h4>Publishing & organization</h4>
							<label for="publisher" class="block">Publisher</label>
							<input type="text" id="publisher" name="publisher" style="width: 100%" value="<?php echo htmlspecialchars($_POST['publisher']) ?>" class="bibtex" tabindex="16" />

							<label for="location" class="block">Location <em>of publisher</em></label>
							<input type="text" id="location" name="location" style="width: 100%" value="<?php echo htmlspecialchars($_POST['location']) ?>" class="bibtex" tabindex="17" />

							<label for="howpublished" class="block">How published</label>
							<input type="text" id="howpublished" name="howpublished" style="width: 100%" value="<?php echo htmlspecialchars($_POST['howpublished']) ?>" class="bibtex" tabindex="18" />

							<label for="organization" class="block">Organization</label>
							<input type="text" id="organization" name="organization" style="width: 100%" value="<?php echo htmlspecialchars($_POST['organization']) ?>" class="bibtex" tabindex="19" />

							<label for="institution" class="block">Institution</label>
							<input type="text" id="institution" name="institution" style="width: 100%" value="<?php echo htmlspecialchars($_POST['institution']) ?>" class="bibtex" tabindex="20" />

							<label for="school" class="block">School</label>
							<input type="text" id="school" name="school" style="width: 100%" value="<?php echo htmlspecialchars($_POST['school']) ?>" class="bibtex" tabindex="21" />

							<label for="address" class="block">Address</label>
							<input type="text" id="address" name="address" style="width: 100%" value="<?php echo htmlspecialchars($_POST['address']) ?>" class="bibtex" tabindex="22" />
						</div>

						<div class="unit bibtex"><h4>Pagination</h4>
							<label for="pages" class="block">Pages</label>
							<input type="text" id="pages" name="pages" style="width: 50%" value="<?php echo htmlspecialchars(str_replace('--', '-', $_POST['pages'])) ?>" class="bibtex" tabindex="23" />
						</div>

						<div class="unit"><h4>Descriptional stuff</h4>
							<label for="note" class="block">Note</label>
							<textarea id="note" name="note" cols="10" rows="10" style="width: 100%" class="bibtex" tabindex="24"><?php echo htmlspecialchars($_POST['note']) ?></textarea>

							<label for="abstract" class="block">Abstract</label>
							<textarea id="abstract" name="abstract" cols="10" rows="10" style="width: 100%" class="collapsible" tabindex="25"><?php echo htmlspecialchars($_POST['abstract']) ?></textarea>

							<label for="userfields" class="block">User fields</label>
							<textarea id="userfields" name="userfields" cols="10" rows="10" style="width: 100%" class="collapsible" tabindex="6"><?php echo htmlspecialchars($_POST['userfields']) ?></textarea>
						</div>

						<div class="unit"><h4>Identification</h4>
							<label for="isbn" class="block">ISBN <em>for books</em></label>
							<input type="text" id="isbn" name="isbn" style="width: 100%" value="<?php echo htmlspecialchars($_POST['isbn']) ?>" class="collapsible" tabindex="28" />

							<label for="issn" class="block">ISSN <em>for journals</em></label>
							<input type="text" id="issn" name="issn" style="width: 100%" value="<?php echo htmlspecialchars($_POST['issn']) ?>" class="collapsible" tabindex="29" />

							<label for="doi" class="block">DOI <em>of publication</em></label>
							<input type="text" id="doi" name="doi" style="width: 100%" value="<?php echo htmlspecialchars($_POST['doi']) ?>" class="collapsible" tabindex="30" />

							<label for="url" class="block">URL <em>of publication</em></label>
							<input type="text" id="url" name="url" style="width: 100%" value="<?php echo htmlspecialchars($_POST['url']) ?>" class="collapsible" tabindex="31" />
						</div>

						<div class="submit"><input type="submit" value="save" tabindex="32" /></div>
					</form>

					<script type="text/javascript">
						/* <![CDATA[ */
			<?php
			echo 'var bibliographie_publications_editor_pub_id = ';
			if (is_array($publication))
				echo $publication['pub_id'];
			else
				echo 0;
			echo ';';
			?>
						$(function() {
							$('#pub_type').bind('mouseup keyup', function (event) {
								delayRequest('bibliographie_publications_show_fields', Array(event.target.value));
							});

							$('#title').bind('mouseup keyup', function (event) {
								delayRequest('bibliographie_publications_check_title', Array(event.target.value));
							});

							bibliographie_authors_input_tokenized('author', <?php echo json_encode($prePopulateAuthor) ?>);
							bibliographie_authors_input_tokenized('editor', <?php echo json_encode($prePopulateEditor) ?>);
							bibliographie_tags_input_tokenized('tags', <?php echo json_encode($prePopulateTags) ?>);
							bibliographie_topics_input_tokenized('topics', 'topicsContainer', <?php echo json_encode($prePopulateTopics) ?>);

							bibliographie_publications_show_fields($('#pub_type').val());

							$('#content input, #content textarea').charmap();

							bibliographie_publications_check_title($('#title').val());


						});
						/* ]]> */
					</script>
			<?php
			bibliographie_charmap_print_charmap();
		}
		break;
	//case
	case 'showPublication':
		$publication = bibliographie_publications_get_data($_GET['pub_id']);

		if (is_object($publication)) {
			$publication = (array) $publication;
			bibliographie_history_append_step('publications', 'Showing publication ' . htmlspecialchars($publication['title']));
			?>

					<em style="float: right">
						<a href="<?php echo BIBLIOGRAPHIE_WEB_ROOT ?>/notes/?task=noteEditor&amp;pub_id=<?php echo (int) $publication['pub_id'] ?>"><?php echo bibliographie_icon_get('note-add') ?> Add note</a>
						<a href="<?php echo BIBLIOGRAPHIE_WEB_ROOT ?>/publications/?task=publicationEditor&amp;pub_id=<?php echo ((int) $publication['pub_id']) ?>"><?php echo bibliographie_icon_get('page-white-edit') ?> Edit</a>
						<a href="javascript:;" onclick="bibliographie_publications_confirm_delete(<?php echo (int) $publication['pub_id'] ?>)"><?php echo bibliographie_icon_get('page-white-delete') ?> Delete</a>
					</em>
					<h3><?php echo htmlspecialchars($publication['title']) ?></h3>
					<?php
					echo bibliographie_publications_print_list(
						array($publication['pub_id']), BIBLIOGRAPHIE_WEB_ROOT . '/publications/?task=publicationEditor&amp;pub_id=' . ((int) $publication['pub_id']), array(
						'onlyPublications' => true
						)
					);
					?>

					<table class="dataContainer">
						<thead>
							<tr>
								<th colspan="2">
									<a href="javascript:;" onclick="$('tbody').toggle('blind');" style="float: right;">Toggle information</a>
									Extended information
								</th>
							</tr>
						</thead>
						<tbody>
			<?php
			foreach ($bibliographie_publication_data as $dataKey => $dataLabel) {
				if (!empty($publication[$dataKey])) {
					if ($dataKey == 'url')
						$publication['url'] = '<a href="' . $publication['url'] . '">' . $publication['url'] . '</a>';

					elseif ($dataKey == 'booktitle')
						$publication['booktitle'] = '<a href="' . BIBLIOGRAPHIE_WEB_ROOT . '/publications/?task=showContainer&amp;type=book&amp;container=' . htmlspecialchars($publication['booktitle']) . '">' . htmlspecialchars($publication['booktitle']) . '</a>';

					elseif ($dataKey == 'journal')
						$publication['journal'] = '<a href="' . BIBLIOGRAPHIE_WEB_ROOT . '/publications/?task=showContainer&amp;type=journal&amp;container=' . htmlspecialchars($publication['journal']) . '">' . htmlspecialchars($publication['journal']) . '</a>';

					elseif ($dataKey == 'user_id')
						$publication['user_id'] = bibliographie_user_get_name($publication['user_id']);

					else
						$publication[$dataKey] = htmlspecialchars($publication[$dataKey]);

					echo '<tr><td><strong>' . $dataLabel . '</strong></td><td>' . $publication[$dataKey] . '</td></tr>';
				}elseif (in_array($dataKey, array('authors', 'editors', 'topics', 'tags'))) {
					$notEmpty = false;
					if ($dataKey == 'authors') {
						$authors = bibliographie_publications_get_authors($publication['pub_id'], 'name');
						if (is_array($authors) and count($authors) > 0) {
							$notEmpty = true;

							foreach ($authors as $author)
								$publication['authors'] .= bibliographie_authors_parse_data($author, array('linkProfile' => true)) . '<br />';
						}
					} elseif ($dataKey == 'editors') {
						$editors = bibliographie_publications_get_editors($publication['pub_id'], 'name');
						if (is_array($editors) and count($editors) > 0) {
							$notEmpty = true;

							foreach ($editors as $editor)
								$publication['editors'] .= bibliographie_authors_parse_data($editor, array('linkProfile' => true)) . '<br />';
						}
					} elseif ($dataKey == 'topics') {
						$topics = bibliographie_publications_get_topics($publication['pub_id']);
						if (is_array($topics) and count($topics) > 0) {
							$notEmpty = true;

							foreach ($topics as $topic)
								$publication['topics'] .= bibliographie_topics_parse_name($topic, array('linkProfile' => true)) . '<br />';
						}
					} elseif ($dataKey == 'tags') {
						$tags = bibliographie_publications_get_tags($publication['pub_id']);
						if (is_array($tags) and count($tags) > 0) {
							$notEmpty = true;

							foreach ($tags as $tag)
								$publication['tags'] .= bibliographie_tags_parse_tag($tag, array('linkProfile' => true)) . '<br />';
						}
					}

					if ($notEmpty)
						echo '<tr><td><strong>' . $dataLabel . '</strong></td><td>' . $publication[$dataKey] . '</td></tr>';
				}
			}
			?>

						</tbody>
					</table>

							<?php
							$notes = bibliographie_publications_get_notes($publication['pub_id']);
							if (count($notes) > 0) {
								echo '<h3>Notes</h3>';
								foreach ($notes as $note)
									echo bibliographie_notes_print_note($note->note_id);
							}
							?>

					<h3>Attachments</h3>
					<div style="background: #9d9; border: 1px solid #0a0; color: #fff; float: right; margin: 0 0 10px 10px; padding: 5px;">
						<label for="fileupload">Add files</label>
						<input id="fileupload" type="file" name="files[]" multiple="multiple" />
					</div>
					This is a list of attached files. You can add new files by using the form on the right side or simply dropping them into the dropzone.
					<div id="attachments">
					<?php
					if (is_array(bibliographie_publications_get_attachments($publication['pub_id']))) {
						if (count(bibliographie_publications_get_attachments($publication['pub_id'])) > 0)
							foreach (bibliographie_publications_get_attachments($publication['pub_id']) as $att_id)
								echo bibliographie_attachments_parse($att_id);
						else
							echo '<p class="notice">No files are attached.</p>';
					}
					?>

					</div>
					<div id="dropzone" class="fade well">
						Drop files here to attach them to the publication!
						<div id="fileupload-progress"></div>
					</div>

					<script type="text/javascript">
						/* <![CDATA[ */
						$(function () {
							$('tbody').hide();

							$('#fileupload').fileupload({
								'dataType': 'json',
								'url': bibliographie_web_root+'/publications/ajax.php?task=uploadAttachment',
								'done': function (e, data) {
									bibliographie_publications_register_attachment(data.result[0].original_name, data.result[0].name, data.result[0].type);
								}
							}).on('fileuploadstart', function () {
								var widget = $(this),
								progressElement = $('#fileupload-progress').fadeIn(),
								interval = 500,
								total = 0,
								loaded = 0,
								loadedBefore = 0,
								progressTimer,
								progressHandler = function (e, data) {
									loaded = data.loaded;
									total = data.total;
								},
								stopHandler = function () {
									widget
									.unbind('fileuploadprogressall', progressHandler)
									.unbind('fileuploadstop', stopHandler);
									window.clearInterval(progressTimer);
									progressElement.fadeOut(function () {
										progressElement.html('');
									});
								},
								formatTime = function (seconds) {
									var date = new Date(seconds * 1000);
									return ('0' + date.getUTCHours()).slice(-2) + ':' +
										('0' + date.getUTCMinutes()).slice(-2) + ':' +
										('0' + date.getUTCSeconds()).slice(-2);
								},
								formatBytes = function (bytes) {
									if (bytes >= 1000000000) {
										return (bytes / 1000000000).toFixed(2) + ' GB';
									}
									if (bytes >= 1000000) {
										return (bytes / 1000000).toFixed(2) + ' MB';
									}
									if (bytes >= 1000) {
										return (bytes / 1000).toFixed(2) + ' KB';
									}
									return bytes + ' B';
								},
								formatPercentage = function (floatValue) {
									return (floatValue * 100).toFixed(2) + ' %';
								},
								updateProgressElement = function (loaded, total, bps) {
									progressElement.html(
									formatBytes(bps) + 'ps | ' +
										formatTime((total - loaded) / bps) + ' | ' +
										formatPercentage(loaded / total) + ' | ' +
										formatBytes(loaded) + ' / ' + formatBytes(total)
								);
								},
								intervalHandler = function () {
									var diff = loaded - loadedBefore;
									if (!diff) {
										return;
									}
									loadedBefore = loaded;
									updateProgressElement(
									loaded,
									total,
									diff * (1000 / interval)
								);
								};
								widget
								.on('fileuploadprogressall', progressHandler)
								.on('fileuploadstop', stopHandler);
								progressTimer = window.setInterval(intervalHandler, interval);
							});
						});

						$(document).on('dragover', function (e) {
							var dropZone = $('#dropzone'),
							timeout = window.dropZoneTimeout;
							if (!timeout) {
								dropZone.addClass('in');
							} else {
								clearTimeout(timeout);
							}
							if (e.target === dropZone[0]) {
								dropZone.addClass('hover');
							} else {
								dropZone.removeClass('hover');
							}
							window.dropZoneTimeout = setTimeout(function () {
								window.dropZoneTimeout = null;
								dropZone.removeClass('in hover');
							}, 100);
						}).on('drop dragover', function (e) {
							e.preventDefault();
						});

						function bibliographie_publications_register_attachment (name, location, type) {
							if($('#attachments div.bibliographie_attachment').length == 0)
								$('#attachments').empty();

							$.ajax({
								'url': bibliographie_web_root+'/publications/ajax.php',
								'data': {
									'task': 'registerAttachment',
									'name': name,
									'location': location,
									'type': type,
									'pub_id': <?php echo $publication['pub_id'] ?>
								},
								'success': function (html) {
									$('#attachments').append(html);
								}
							});
						}
						/* ]]> */
					</script>
			<?php
		}else {
			bibliographie_history_append_step('publications', 'Publication does not exist', false);
			echo '<p class="error">Publication was not found!</p>';
		}
		break;
}

require BIBLIOGRAPHIE_ROOT_PATH . '/close.php';
