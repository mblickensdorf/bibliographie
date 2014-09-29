
function showSearchPub(){
	document.getElementById("PubList").style.display = 'block';
	document.getElementById("TopList").style.display = 'none';
	document.getElementById("TagList").style.display = 'none';
	document.getElementById("AuthorList").style.display = 'none';
	document.getElementById("PubListBox").className = "active_cell";
	document.getElementById("TopListBox").className = "inactive_cell";
	document.getElementById("TagListBox").className = "inactive_cell";
	document.getElementById("AuthorsListBox").className = "inactive_cell";
}

function showSearchTop(){
	document.getElementById("PubList").style.display = 'none';
	document.getElementById("TopList").style.display = 'block';
	document.getElementById("TagList").style.display = 'none';
	document.getElementById("AuthorList").style.display = 'none';
	document.getElementById("PubListBox").className = "inactive_cell";
	document.getElementById("TopListBox").className = "active_cell";
	document.getElementById("TagListBox").className = "inactive_cell";
	document.getElementById("AuthorsListBox").className = "inactive_cell";
}

function showSearchTag(){
	document.getElementById("PubList").style.display = 'none';
	document.getElementById("TopList").style.display = 'none';
	document.getElementById("TagList").style.display = 'block';
	document.getElementById("AuthorList").style.display = 'none';
	document.getElementById("PubListBox").className = "inactive_cell";
	document.getElementById("TopListBox").className = "inactive_cell";
	document.getElementById("TagListBox").className = "active_cell";
	document.getElementById("AuthorsListBox").className = "inactive_cell";
}

function showSearchAuthor(){
	document.getElementById("PubList").style.display = 'none';
	document.getElementById("TopList").style.display = 'none';
	document.getElementById("TagList").style.display = 'none';
	document.getElementById("AuthorList").style.display = 'block';
	document.getElementById("PubListBox").className = "inactive_cell";
	document.getElementById("TopListBox").className = "inactive_cell";
	document.getElementById("TagListBox").className = "inactive_cell";
	document.getElementById("AuthorsListBox").className = "active_cell";
}

function showLink(e) {
	var elem = event.target || event.srcElement;
	if (elem.style) elem.style.cursor="move";
}
