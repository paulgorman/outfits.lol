<?
/* admin html templates */

/*
 *  htmlAdminLogin() - the "who goes there" page in front of /admin.
 *
 *  Rebuilt mobile first (Phase 3), because the old one was a grid of
 *  col1/col2 divs with no viewport tag, so on a phone it rendered at
 *  desktop width and shrank to ant size. Now: labels above inputs, inputs
 *  the full width of the phone, one big button. Styles live at the bottom
 *  of templates/css/admin.css, under the "MOBILE FIRST" banner.
 *
 *  $message is the "nope, wrong password" text from the login check, or empty.
 */
function htmlAdminLogin($message) {
	?><!DOCTYPE html>
	<html lang="en">
		<head>
			<meta charset="utf-8">
			<!-- THIS is the line that makes phones stop pretending to be a 980px desktop -->
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<title>Outfits.lol Admin Login</title>
			<link rel="stylesheet" type="text/css" href="/templates/css/responsiveboilerplate.css">
			<link rel="stylesheet" type="text/css" href="/templates/css/irev.css">
			<link rel="stylesheet" type="text/css" href="/templates/css/admin.css">
		</head>
		<body class="AdminLoginPage">
			<form method="POST" action="/login" class="AdminLoginBox dark">

				<div class="AdminLoginTitle">Outfits.lol Admin</div>

				<? if (!isEmpty($message)) { ?>
					<div class="AdminLoginMessage"><?= $message; ?></div>
				<? } ?>

				<!-- autocapitalize off: otherwise the iPhone helpfully types "Presence" with a capital P -->
				<label class="AdminLoginLabel" for="AdminLoginUsername">Username</label>
				<input class="AdminLoginInput" type="text" name="username" id="AdminLoginUsername"
					autocomplete="username" autocapitalize="none" autocorrect="off" spellcheck="false">

				<!-- autocomplete="current-password" lets the iPhone offer the saved password -->
				<label class="AdminLoginLabel" for="AdminLoginPassword">Password</label>
				<input class="AdminLoginInput" type="password" name="password" id="AdminLoginPassword"
					autocomplete="current-password">

				<input class="AdminLoginButton" type="submit" value="Log In">

			</form>
		</body>
	</html>
	<?
}


function AdminHead($curfunction,$adminfunctions) {
	$tempadminfunctionsflipped = array_flip ($adminfunctions);
	?><!DOCTYPE html>
		<html lang="en">
			<head>
				<meta http-equiv="X-UA-Compatible" content="IE=edge">
				<meta charset="utf-8">
				<!-- without this, phones draw the admin 980px wide and shrink it to ant size -->
				<meta name="viewport" content="width=device-width, initial-scale=1">
				<title>Outfits.lol Admin - <?= $tempadminfunctionsflipped[$curfunction]; ?></title>
				<link rel="stylesheet" type="text/css" href="/templates/css/admin.css" />
	  		<link rel="stylesheet" type="text/css" href="/templates/css/jquery.bsmselect.css" />
	  		<link href="/templates/css/jquery-ui.css" rel="stylesheet" type="text/css" />
	  		<script type="text/javascript" src="/templates/js/jquery.js"></script>
	  		<script type="text/javascript" src="/templates/js/jquery-ui.js"></script>
	  		<script type="text/javascript" src="/templates/js/jquery.bsmselect.js"></script>
	  		<script type="text/javascript" src="/templates/js/jquery.bsmselect.sortable.js"></script>
	  		<script type="text/javascript" src="/templates/js/jquery.bsmselect.compatibility.js"></script>
			</head>
			<body>
				<!-- page name gets its own line: "Website Administration - Overview" wrapped three times on a phone -->
				<div class='adminTitle'>
					<span class='adminTitleSite'><a href="/">Outfits.lol</a> Admin</span>
					<span class='adminTitlePage'><?= $tempadminfunctionsflipped[$curfunction]; ?></span>
				</div>
	<?
}

function AdminNav($curfunction,$adminfunctions) {
	echo "<nav class='adminNavHolder'>";
	foreach ($adminfunctions as $nicename => $url) {
		if ($curfunction == $url) {
			?>
					<div class='adminNavItemSelected'>
						<a href='/admin/<?= $url ?>'><B><?= strtoupper($nicename) ?></B></a>
					</div>
			<?
		} else {
			?>
					<div class='adminNavItem'>
						<a href='/admin/<?= $url ?>'><?= strtoupper($nicename) ?></a>
					</div>
			<?
		}
	}
	echo "</nav>";
}

function AdminsamplesButtonBar() {
	?>
		<div  class="adminNavSubHolder">
			<div class="adminNavSubItem"><a href='/admin/samples/add_new'>Add New Sample</a></div>
			<div class="adminNavSubItem"><a href='/admin/samples/list_new'>List Newest Samples</a></div>
			<div class="adminNavSubItem"><a href='/admin/samples/list_feat'>List Featured Samples</a></div>
			<div class="adminNavSubItem"><a href='/admin/samples/list_secret'>List Hidden Samples</a></div>
			<div class="adminNavSubItem"><a href='/admin/samples/list_all'>List All Samples</a></div>
			<div class="adminNavSubItem"><form method="POST" action="/admin/samples/search/" id="search"><input type="hidden" name="function" value="search"><input type="text" name="q" placeholder="Search..."></form></div>
		</div>
	<?
}


/*
 * ======================================================================
 *  THE ADMIN PAGES, MOBILE FIRST (2026 rewrite, Phase 3 of PLAN.md)
 * ======================================================================
 *
 * Everything below this banner was rebuilt from the 2016 floaty-div
 * version. The whole admin now uses a small set of building blocks,
 * styled at the bottom of templates/css/admin.css:
 *
 *   adminPanel          a dark purple box, 960px max, full width on a phone
 *   adminPanelHeader    the title row of a panel (and the page links, if any)
 *   adminList           a stack of adminListRow, one per thing
 *   adminListRowMain    the big tappable part of a row: name plus details
 *   adminBadge          a little word-pill: "Featured", "Hidden", "Public"
 *   adminButton         a real button you can hit with a thumb
 *   adminField          a label sitting on top of its input (side by side
 *                       on a desktop)
 *   adminChecks         a row of big checkboxes, each with its words
 *
 * What changed besides the looks:
 *   - "Edit" and "show me the samples" are plain links now, not
 *     onclick-submits-a-hidden-form. Long-press, middle-click, and the
 *     back button all work. Deletes are still POST forms, because a link
 *     that deletes things is how a web crawler empties your closet.
 *   - The old icon soup (green dot, grey dot, blue dot, stop sign) is now
 *     words. Nobody remembers what the blue dot meant. Not even me.
 *   - Every form field keeps its exact old name="", so the save
 *     functions in shared.php never noticed anything happened.
 */


/*
 * ShowAdminDeleteFormButton: a tiny POST form holding one red "Delete"
 * button. The delete functions in shared.php show an "are you sure?"
 * page first, so this one tap is not the point of no return.
 */
function ShowAdminDeleteFormButton($form_action_address, $hidden_form_values, $what_gets_deleted) {
	?>
		<form class="adminInlineForm" method="POST" action="<?= $form_action_address ?>">
			<? foreach ($hidden_form_values as $hidden_field_name => $hidden_field_value) { ?>
				<input type="hidden" name="<?= $hidden_field_name ?>" value="<?= htmlspecialchars($hidden_field_value, ENT_QUOTES) ?>">
			<? } ?>
			<button type="submit" class="adminButton adminButtonSmall adminButtonDanger" title="Delete <?= htmlspecialchars($what_gets_deleted, ENT_QUOTES) ?>">Delete</button>
		</form>
	<?
}

function AdminShowNewAdminForm() {
	?>
		<form method="POST" action="/admin/admin_users" enctype="multipart/form-data">
			<input type="hidden" name="function" value="save_new_admin">
			<section class="adminPanel adminPanelAdd">
				<div class="adminPanelHeader">
					<h2 class="adminPanelTitle">Add a new admin user</h2>
				</div>
				<div class="adminField">
					<label for="new_admin_username">Username</label>
					<input type="text" name="username" id="new_admin_username" autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false">
				</div>
				<div class="adminField">
					<label for="new_admin_password">Password</label>
					<input type="password" name="password" id="new_admin_password" autocomplete="new-password">
				</div>
				<div class="adminFormButtons">
					<button type="submit" class="adminButton adminButtonPrimary">Add User</button>
				</div>
			</section>
		</form>
	<?
}

function AdminShowAdminsList($admins) {
	// there is no "edit an admin" page (the old rows posted to /admin/admins/edit,
	// which goes nowhere), so these rows are just names, not links
	?>
		<section class="adminPanel">
			<div class="adminPanelHeader">
				<h2 class="adminPanelTitle">Admin users</h2>
				<a class="adminButton adminButtonSmall" href="/admin/admin_users/add_admin">New Administrator</a>
			</div>
			<div class="adminList">
				<? foreach ($admins as $admin_username) { ?>
					<div class="adminListRow">
						<div class="adminListRowMain">
							<span class="adminListRowName"><?= $admin_username; ?></span>
						</div>
					</div>
				<? } ?>
			</div>
		</section>
	<?
}

function AdminPagesButtonBar($pages) {
	?>
		<div  class="adminNavSubHolder">
			<?
				foreach ($pages as $url => $name) {
					echo "<div class=\"adminNavSubPagesItem\"><a href=\"/admin/pages/edit/$url\">$name</a></div>\n";
				}
			?>
		</div>
	<?
}

function AdminPagesListPage($pagedata) {
	?>
		<section class="adminPanel">
			<div class="adminPanelHeader">
				<h2 class="adminPanelTitle">Pages</h2>
			</div>
			<div class="adminList">
				<? foreach ($pagedata as $page_url => $page_details) { ?>
					<div class="adminListRow">
						<a class="adminListRowMain" href="/admin/pages/edit/<?= $page_url; ?>">
							<span class="adminListRowName"><?= $page_details['name']; ?></span>
							<span class="adminListRowDetail">Updated <?= nicetime($page_details['lastupdated']); ?></span>
						</a>
						<div class="adminListRowActions">
							<a class="adminButton adminButtonSmall" href="/admin/pages/edit/<?= $page_url; ?>">Edit</a>
						</div>
					</div>
				<? } ?>
			</div>
		</section>
	<?
}

function AdminPagesEditPage($pagedata) {
	?>
		<section class="adminPanel">
			<div class="adminPanelHeader">
				<h2 class="adminPanelTitle"><?= MakeCase($pagedata['pagename']); ?> Page Editor</h2>
				<span class="adminPanelSubtitle">Last updated <?= nicetime($pagedata['htmltime']) ?></span>
			</div>
			<link rel="stylesheet" type="text/css" href="/templates/redactor/redactor.css">
			<script src="/templates/redactor/redactor.min.js"></script>
			<script src="/templates/redactor/fontcolor.js"></script>
			<script src="/templates/redactor/fontfamily.js"></script>
			<script src="/templates/redactor/fontsize.js"></script>
			<script type="text/javascript">
				$(function() {
					$('#redactor_content').redactor(
						{
							minHeight: 500,
							focus: true,
						  iframe: true,
							plugins: ['fontfamily','fontsize','fontcolor'],
					    css: ['/templates/css/responsiveboilerplate.css', '/templates/css/irev.css'],
							imageUpload: '/adminpageimageupload',
							clipboardUploadUrl: '/adminpageimageclipboard'
						}
					);
				});
			</script>
			<form method="POST" action="/admin/pages/update">
				<input type="hidden" name="pageurl" value="<?= $pagedata['pagename']; ?>">
				<div class="adminPageEditor">
					<textarea id="redactor_content" name="content">
						<?= stripslashes($pagedata['html']); ?>
					</textarea>
				</div>
				<div class="adminFormButtons">
					<button type="submit" class="adminButton adminButtonPrimary" name="send" value="Save">Save</button>
				</div>
			</form>
			<form method="POST" action="/admin/pages/revert">
				<input type="hidden" name="pageurl" value="<?= $pagedata['pagename']; ?>">
				<div class="adminFormButtons">
					<button type="submit" class="adminButton" name="Revert" value="Revert">Revert to content from <?= niceTime($pagedata['undotime']); ?></button>
				</div>
			</form>
		</section>
	<?
}


/*
 * ShowAdminSampleListPanel: the ONE list of samples. The old file had this
 * same block of HTML copy/pasted six times (all, newest, featured, hidden,
 * by category, by tag, by search), and every copy had drifted a little.
 * Now the six Adminsample... functions below just pick a title and hand
 * their samples over to this one.
 *
 * $samples can be a plain array or a mysqli result: foreach eats both.
 * $page_navigation_html is the "First / Previous / Page 2 of 9" links,
 * or "" for lists that are not split into pages.
 */
function ShowAdminSampleListPanel($panel_title, $samples, $page_navigation_html) {
	$how_many_samples_shown = 0;
	?>
		<section class="adminPanel">
			<div class="adminPanelHeader">
				<h2 class="adminPanelTitle"><?= $panel_title ?></h2>
				<? if ($page_navigation_html !== "") { ?>
					<nav class="adminPager"><?= $page_navigation_html ?></nav>
				<? } ?>
			</div>
			<div class="adminList">
				<?
					foreach ($samples as $sample_details) {
						ShowAdminSampleListRow($sample_details);
						$how_many_samples_shown++;
					}
				?>
				<? if ($how_many_samples_shown === 0) { ?>
					<p class="adminEmptyList">Nothing here yet. The closet echoes.</p>
				<? } ?>
			</div>
			<? if ($page_navigation_html !== "" && $how_many_samples_shown > 5) { ?>
				<!-- page links again at the bottom, so a long list doesn't mean scrolling back up -->
				<div class="adminPanelFooter">
					<nav class="adminPager"><?= $page_navigation_html ?></nav>
				</div>
			<? } ?>
		</section>
	<?
}

/*
 * ShowAdminSampleListRow: one sample in a list. The whole name area is a
 * link to the edit page; "View" opens the public page.
 * The edit page is /admin/samples/edit/<oid>: shared.php has always
 * accepted the oid in the URL (its "hack for direct URL access" comment).
 */
function ShowAdminSampleListRow($sample_details) {
	$sample_edit_address = "/admin/samples/edit/" . (int)$sample_details['oid'];
	$sample_public_address = "/sample/" . $sample_details['url'];

	// only the search results ask the database for display_name
	$sample_display_name = "";
	if (isset($sample_details['display_name'])) {
		$sample_display_name = trim((string)$sample_details['display_name']);
	}
	?>
		<div class="adminListRow">
			<a class="adminListRowMain" href="<?= $sample_edit_address ?>">
				<span class="adminListRowName adminListRowRealName"><?= $sample_details['name'] ?></span>
				<? if ($sample_display_name !== "" && $sample_display_name !== $sample_details['name']) { ?>
					<span class="adminListRowDetail">a.k.a. <?= $sample_display_name ?></span>
				<? } ?>
				<? if (!isEmpty($sample_details['slug'])) { ?>
					<span class="adminListRowDetail"><?= $sample_details['slug'] ?></span>
				<? } ?>
				<span class="adminListRowDetail adminListRowDetailQuiet">Updated <?= nicetime($sample_details['last_updated']); ?></span>
			</a>
			<div class="adminListRowBadges">
				<? ShowAdminSampleStatusBadges($sample_details); ?>
			</div>
			<div class="adminListRowActions">
				<a class="adminButton adminButtonSmall" href="<?= $sample_edit_address ?>">Edit</a>
				<a class="adminButton adminButtonSmall" href="<?= $sample_public_address ?>">View</a>
			</div>
		</div>
	<?
}

/*
 * ShowAdminSampleStatusBadges: was AdminsampleListIconColumn(), the
 * green/grey/blue dots. Same three rules, now in words.
 */
function ShowAdminSampleStatusBadges($sample_details) {
	if ((int)$sample_details['is_highlighted'] === 1) {
		echo "<span class='adminBadge adminBadgeGood'>Featured</span>";
	}
	if ((int)$sample_details['is_active'] === 0) {
		echo "<span class='adminBadge adminBadgeOff'>Inactive</span>";
	} elseif ((int)$sample_details['is_active'] === 1 && (int)$sample_details['is_searchable'] === 0) {
		echo "<span class='adminBadge adminBadgeQuiet'>Hidden from search</span>";
	}
}

function AdminsampleListPage($samples,$page) {
	ShowAdminSampleListPanel("All samples", $samples, ShowPageNav(FigurePageNav("list_all",$page)));
}

function AdminsampleListPageNew($samples,$page) {
	ShowAdminSampleListPanel("Recently added and updated", $samples, ShowPageNav(FigurePageNav("list_new",$page)));
}

function AdminsampleListPageFeat($samples,$page) {
	ShowAdminSampleListPanel("Featured samples", $samples, ShowPageNav(FigurePageNav("list_feat",$page)));
}

function AdminsampleListPageSecret($samples,$page) {
	ShowAdminSampleListPanel("Hidden and inactive samples", $samples, ShowPageNav(FigurePageNav("list_secret",$page)));
}

function AdminsampleListPageByCategory($samples,$page) {
	$panel_title = "All " . mysqli_num_rows($samples) . " samples in " . CategoryNameFromURL($_REQUEST['categoryurl']);
	ShowAdminSampleListPanel($panel_title, $samples, "");
}

function AdminsampleListPageByTag($samples,$page) {
	$panel_title = "All " . mysqli_num_rows($samples) . " samples tagged " . MakeCase(TagNameFromTID($_REQUEST['tid']));
	ShowAdminSampleListPanel($panel_title, $samples, "");
}

function AdminsampleListPageBySearchResult($samples,$page) {
	// the search word arrives as ?q= from the search box, or as the end of
	// the URL (/admin/samples/search/purple), so show whichever one we got
	$search_words_shown = htmlspecialchars(strip_tags(MakeCase(trim($_REQUEST['q'] ?? ''))));
	$search_words_shown .= htmlspecialchars(strip_tags(MakeCase(trim($_REQUEST['listpage'] ?? ''))));
	$panel_title = "All " . mysqli_num_rows($samples) . " samples matching \"" . $search_words_shown . "\"";
	ShowAdminSampleListPanel($panel_title, $samples, "");
}

function ShowPageNav($pageinfo) {
	$returnhtml = "";
	if ($pageinfo['page'] != $pageinfo['first']) {
		$returnhtml .= "<a class='adminPagerLink' href='/admin/samples/". $pageinfo['type'] ."/". $pageinfo['first'] ."'>First</a>";
	}
	if ($pageinfo['previous'] != $pageinfo['page'] && $pageinfo['previous'] != $pageinfo['first']) {
		$returnhtml .= "<a class='adminPagerLink' href='/admin/samples/". $pageinfo['type'] ."/". $pageinfo['previous'] ."'>Previous</a>";
	}
	$returnhtml .= "<span class='adminPagerHere'>Page ". $pageinfo['page'] ." of ". $pageinfo['maximum'] ."</span>";
	if ($pageinfo['next'] != $pageinfo['page'] && $pageinfo['next'] != $pageinfo['maximum']) {
		$returnhtml .= "<a class='adminPagerLink' href='/admin/samples/". $pageinfo['type'] ."/". $pageinfo['next'] ."'>Next</a>";
	}
	if ($pageinfo['page'] != $pageinfo['maximum']){
		$returnhtml .= "<a class='adminPagerLink' href='/admin/samples/". $pageinfo['type'] ."/". $pageinfo['maximum'] ."'>Last</a>";
	}
	return $returnhtml;
}

function AdminShowSeasons($every_season) {
	// $every_season is sid => season name, from GetEverySeason() in shared.php.
	// No "show me the samples" button yet: ShowAdminPage() has no
	// search_season. Add one when the outfit matcher wants it.
	?>
		<section class="adminPanel">
			<div class="adminPanelHeader">
				<h2 class="adminPanelTitle">Seasons</h2>
				<span class="adminPanelSubtitle">A sample with no season is year-round</span>
			</div>
			<div class="adminList">
				<? foreach ($every_season as $season_id => $season_name) { ?>
					<? $season_edit_address = "/admin/seasons_list/edit_season?sid=" . (int)$season_id; ?>
					<div class="adminListRow">
						<a class="adminListRowMain" href="<?= $season_edit_address ?>">
							<span class="adminListRowName"><?= $season_name ?></span>
						</a>
						<div class="adminListRowActions">
							<a class="adminButton adminButtonSmall" href="<?= $season_edit_address ?>">Rename</a>
							<?
								// careful: this deletes right away, no "are you sure?" page
								// (the samples stay, they just lose this season)
								ShowAdminDeleteFormButton(
									"/admin/seasons_list",
									array("sid" => $season_id, "function" => "del_season"),
									$season_name
								);
							?>
						</div>
					</div>
				<? } ?>
				<? if (count($every_season) === 0) { ?>
					<p class="adminEmptyList">No seasons yet. It's always sweater weather in here.</p>
				<? } ?>
			</div>
		</section>

		<form method="POST" action="/admin/seasons_list" enctype="multipart/form-data">
			<input type="hidden" name="function" value="add_season">
			<section class="adminPanel adminPanelAdd">
				<div class="adminPanelHeader">
					<h2 class="adminPanelTitle">Add a new season</h2>
				</div>
				<div class="adminField">
					<label for="new_season_name">Name</label>
					<input type="text" name="season" id="new_season_name" placeholder="Summer">
				</div>
				<div class="adminFormButtons">
					<button type="submit" class="adminButton adminButtonPrimary">Add Season</button>
				</div>
			</section>
		</form>
	<?
}

function AdminShowTags($tags,$quantity) {
	?>
		<section class="adminPanel">
			<div class="adminPanelHeader">
				<h2 class="adminPanelTitle">Tags (<?= $quantity ?>)</h2>
			</div>
			<div class="adminList">
				<? foreach ($tags as $tag_details) { ?>
					<? $tag_edit_address = "/admin/tags_list/edit_tag?tid=" . (int)$tag_details['tid']; ?>
					<div class="adminListRow">
						<a class="adminListRowMain" href="<?= $tag_edit_address ?>">
							<span class="adminListRowName"><?= $tag_details['name'] ?></span>
						</a>
						<div class="adminListRowActions">
							<a class="adminButton adminButtonSmall" href="<?= $tag_edit_address ?>">Edit</a>
							<a class="adminButton adminButtonSmall" href="/admin/tags_list/search_tag?tid=<?= (int)$tag_details['tid'] ?>">Samples</a>
							<?
								ShowAdminDeleteFormButton(
									"/admin/tags_list",
									array("tid" => $tag_details['tid'], "function" => "del_tag"),
									"the " . $tag_details['name'] . " tag"
								);
							?>
						</div>
					</div>
				<? } ?>
				<? if (count($tags) === 0) { ?>
					<p class="adminEmptyList">No tags yet. Adjectives welcome.</p>
				<? } ?>
			</div>
		</section>

		<form method="POST" action="/admin/tags_list" enctype="multipart/form-data">
			<input type="hidden" name="function" value="add_tag">
			<section class="adminPanel adminPanelAdd">
				<div class="adminPanelHeader">
					<h2 class="adminPanelTitle">Add a new tag</h2>
				</div>
				<div class="adminField">
					<label for="new_tag_name">Name</label>
					<input type="text" name="name" id="new_tag_name">
				</div>
				<div class="adminFormButtons">
					<button type="submit" class="adminButton adminButtonPrimary">Add Tag</button>
				</div>
			</section>
		</form>
	<?
}

function AdminShowCategories ($categorieslist) {
	?>
		<section class="adminPanel">
			<div class="adminPanelHeader">
				<h2 class="adminPanelTitle">Categories (<?= count($categorieslist) ?>)</h2>
			</div>
			<div class="adminList">
				<? foreach ($categorieslist as $category_details) { ?>
					<? $category_edit_address = "/admin/categories_list/edit_category?categoryurl=" . urlencode($category_details['url']); ?>
					<div class="adminListRow">
						<a class="adminListRowMain" href="<?= $category_edit_address ?>">
							<span class="adminListRowName"><?= $category_details['category'] ?></span>
							<? if (!isEmpty($category_details['description'])) { ?>
								<span class="adminListRowDetail"><?= $category_details['description'] ?></span>
							<? } ?>
							<span class="adminListRowDetail adminListRowDetailQuiet">/wardrobe/<?= $category_details['url'] ?></span>
						</a>
						<div class="adminListRowBadges">
							<?
								if ($category_details['published']) {
									echo "<span class='adminBadge adminBadgeGood' title='Shown in the public Categories list'>Public</span>";
								} else {
									echo "<span class='adminBadge adminBadgeOff' title='Not in the public Categories list'>Unlisted</span>";
								}
								if ($category_details['highlighted']) {
									echo "<span class='adminBadge adminBadgeAccent' title='Shown in the home page carousel'>Highlighted</span>";
								}
								if ($category_details['subs'] > 0) {
									echo "<span class='adminBadge adminBadgeQuiet'>Has sub-categories</span>";
								}
							?>
						</div>
						<div class="adminListRowActions">
							<a class="adminButton adminButtonSmall" href="<?= $category_edit_address ?>">Edit</a>
							<a class="adminButton adminButtonSmall" href="/admin/categories_list/search_category?categoryurl=<?= urlencode($category_details['url']) ?>">Samples</a>
							<?
								ShowAdminDeleteFormButton(
									"/admin/categories_list",
									array("categoryurl" => $category_details['url'], "function" => "del_category"),
									"the " . $category_details['category'] . " category"
								);
							?>
						</div>
					</div>
				<? } ?>
				<? if (count($categorieslist) === 0) { ?>
					<p class="adminEmptyList">No categories yet. Nouns welcome.</p>
				<? } ?>
			</div>
		</section>
		<script type="text/javascript">
			<!--
			function showHide(){
				var highlighted = document.getElementById("highlighted");
				var carousel_image = document.getElementById("carousel_image");
				if (highlighted.checked){
					carousel_image.style.visibility = "visible";
				} else {
					carousel_image.style.visibility = "hidden";
				}
			}
			-->
		</script>
		<form method="POST" action="/admin/categories_list" enctype="multipart/form-data">
			<input type="hidden" name="function" value="add_category">
			<section class="adminPanel adminPanelAdd">
				<div class="adminPanelHeader">
					<h2 class="adminPanelTitle">Add a new category</h2>
				</div>
				<div class="adminField">
					<label for="new_category_name">Category Name</label>
					<input type="text" name="form_category" id="new_category_name" value="<?= MakeCase(htmlspecialchars(trim(($_REQUEST['form_category'] ?? '')))); ?>">
				</div>
				<div class="adminField">
					<label for="new_category_url">URL</label>
					<input type="text" placeholder="Optional, made from the name" name="form_url" id="new_category_url" value="<?= htmlspecialchars(trim(($_REQUEST['form_url'] ?? ''))); ?>" style="text-transform: lowercase" autocapitalize="none" autocorrect="off">
				</div>
				<div class="adminField">
					<label for="new_category_description">Description</label>
					<input type="text" name="form_description" id="new_category_description" value="<?= htmlspecialchars(trim(($_REQUEST['form_description'] ?? ''))); ?>">
				</div>
				<div class="adminField">
					<label for="new_category_display_names">Sample Names</label>
					<select name="force_display_names" id="new_category_display_names"><?= DisplayNamesOptionsDropDown($dataarray['cid'] ?? NULL) ?></select>
				</div>
				<div class="adminField">
					<label for="new_category_logo">Category Logo</label>
					<div>
						<input name="filesToUpload[]" class="filesToUpload" id="1" type="file" multiple="" accept="image/png">
						<span class="adminFieldHint">728x90 PNG</span>
					</div>
				</div>
				<div class="adminChecks">
					<label class="adminCheck" title="Publicly Displayed in Categories Listing">
						<input type="checkbox" name="published" id="published" CHECKED>
						Public
					</label>
					<label class="adminCheck" title="Highlighted in the Carousel">
						<input type="checkbox" name="highlighted" id="highlighted" onclick="showHide();">
						Highlighted <span class="adminFieldHint">(1024x680 carousel image)</span>
					</label>
				</div>
				<div class="adminField">
					<span></span>
					<input name="carousel_image" id="carousel_image" style="visibility: hidden;" class="filesToUpload" type="file" multiple="" accept="image/png">
				</div>
				<div class="adminFormButtons">
					<button type="submit" class="adminButton adminButtonPrimary">Add Category</button>
				</div>
			</section>
		</form>
	<?
}

function AdminShowSubCategories ($categorieslist) {
	// No "show samples" button here: search_subcategory was never wired up
	// in ShowAdminPage(), so the old magnifying glass just went back to
	// the category list.
	?>
		<section class="adminPanel">
			<div class="adminPanelHeader">
				<h2 class="adminPanelTitle">Sub-categories</h2>
			</div>
			<div class="adminList">
				<? foreach ($categorieslist as $subcategory_details) { ?>
					<?
						$subcategory_edit_address = "/admin/categories_list/edit_subcategory"
							. "?categoryurl=" . urlencode($subcategory_details['url'])
							. "&amp;parent_cid=" . (int)$subcategory_details['parent_cid'];
					?>
					<div class="adminListRow">
						<a class="adminListRowMain" href="<?= $subcategory_edit_address ?>">
							<span class="adminListRowName"><?= $subcategory_details['category'] ?></span>
							<? if (!isEmpty($subcategory_details['description'])) { ?>
								<span class="adminListRowDetail"><?= $subcategory_details['description'] ?></span>
							<? } ?>
							<span class="adminListRowDetail adminListRowDetailQuiet"><?= $subcategory_details['url'] ?></span>
						</a>
						<div class="adminListRowActions">
							<a class="adminButton adminButtonSmall" href="<?= $subcategory_edit_address ?>">Edit</a>
							<?
								ShowAdminDeleteFormButton(
									"/admin/categories_list",
									array(
										"parent_cid" => $subcategory_details['parent_cid'],
										"categoryurl" => $subcategory_details['url'],
										"function" => "del_subcategory"
									),
									"the " . $subcategory_details['category'] . " sub-category"
								);
							?>
						</div>
					</div>
				<? } ?>
			</div>
		</section>
	<?
}

function AdminShowDeleteConfirmation($id,$desc,$urlDo,$urlCancel,$nextfunction) {
	?>
		<div class="AdminWarning">
			<form method="POST" action="/admin/<?= $urlDo ?>">
				<input type="hidden" name="targetcategoryurl" value="<?= $id ?>">
				<input type="hidden" name="function" value="<?= $nextfunction ?>">
				<p>Are you sure you want to delete <B><?= $desc ?></B>?</p>
				<div class="adminFormButtons">
					<button type="submit" class="adminButton adminButtonDanger">Delete</button>
					<button type="button" class="adminButton" name="Cancel" onclick="window.location='/admin/<?= $urlCancel ?>'">Oops, no, totally not! Go Back! Cancel!</button>
				</div>
			</form>
		</div>
	<?
}

function AdminEditCategory($dataarray) {
	// cid, category, url, description, image_filename, image_id, last_updated
	?>
		<script type="text/javascript">
			<!--
			function showHide(){
				var is_highlighted = document.getElementById("is_highlighted");
				var carousel_image = document.getElementById("carousel_image");
				if (is_highlighted.checked){
					carousel_image.style.visibility = "visible";
				} else {
					carousel_image.style.visibility = "hidden";
				}
			}
			-->
		</script>
		<form method="POST" action="/admin/categories_list" enctype="multipart/form-data">
			<!-- these two used to be <input method="hidden" style="display:none">, a typo that CSS papered over for ten years -->
			<input type="hidden" name="function" value="save_category">
			<input type="hidden" name="form_cid" value="<?= $dataarray['cid'] ?>">
			<section class="adminPanel">
				<div class="adminPanelHeader">
					<h2 class="adminPanelTitle">Edit category "<?= $dataarray['category'] ?>"</h2>
					<span class="adminPanelSubtitle" title="<?= $dataarray['last_updated'] ?>">Last edited <?= nicetime($dataarray['last_updated']) ?></span>
				</div>
				<div class="adminField">
					<label for="edit_category_name">Short Name</label>
					<input type="text" name="form_category" id="edit_category_name" value="<?= $dataarray['category']?>">
				</div>
				<div class="adminField">
					<label for="edit_category_description">Nice Description</label>
					<input type="text" name="form_description" id="edit_category_description" value="<?= $dataarray['description'] ?>">
				</div>
				<div class="adminField">
					<label for="edit_category_url">URL</label>
					<input type="text" name="form_url" id="edit_category_url" value="<?= $dataarray['url'] ?>" style="text-transform: lowercase" autocapitalize="none" autocorrect="off">
				</div>
				<div class="adminField">
					<label for="edit_category_display_names">Sample Names</label>
					<select name="force_display_names" id="edit_category_display_names"><?= DisplayNamesOptionsDropDown($dataarray['cid'] ?? NULL) ?></select>
				</div>
				<div class="adminField">
					<span class="adminFieldLabel">Stored Image</span>
					<div class="adminImagePreview"><img src="/i/category/<?= $dataarray['image_id'] ?>" alt="<?= $dataarray['image_filename'] ?>"></div>
				</div>
				<div class="adminField">
					<span class="adminFieldLabel">Replace Image</span>
					<div>
						<input name="filesToUpload[]" class="filesToUpload" id="1" type="file" multiple="" accept="image/png">
						<span class="adminFieldHint">728x90 PNG. Now: <?= $dataarray['image_filename'] ?></span>
					</div>
				</div>
				<div class="adminChecks">
					<label class="adminCheck" title="Publicly Displayed in Categories Listing">
						<input type="checkbox" id="published" name="published" <?= ($dataarray['published']?'CHECKED ':'') ?>>
						Public
					</label>
					<label class="adminCheck" title="Highlighted in the Featured Carousel">
						<input type="checkbox" id="is_highlighted" onclick="showHide();" name="is_highlighted" <?= ($dataarray['is_highlighted']?'CHECKED ':'') ?>>
						Highlighted <span class="adminFieldHint">(1024x680 carousel image)</span>
					</label>
				</div>
				<?
					// the carousel upload box only shows for highlighted categories. The old page
					// always started it hidden, even when the box was already checked, so you had
					// to un-check and re-check to find it.
					$carousel_upload_visibility = "hidden";
					if ($dataarray['is_highlighted']) {
						$carousel_upload_visibility = "visible";
					}
				?>
				<div class="adminField">
					<span class="adminFieldLabel">Carousel Image</span>
					<div>
						<input name="carousel_image" id="carousel_image" style="visibility: <?= $carousel_upload_visibility ?>;" class="filesToUpload" type="file" multiple="">
					</div>
				</div>
				<? if (!isEmpty($dataarray['carousel_id'])) { ?>
					<div class="adminField">
						<span class="adminFieldLabel">Current Carousel</span>
						<div class="adminImagePreview"><img src="/i/category/<?= $dataarray['carousel_id']; ?>" alt="Carousel image"></div>
					</div>
				<? } ?>
				<div class="adminFormButtons">
					<button type="submit" class="adminButton adminButtonPrimary">Update Category</button>
					<button type="button" class="adminButton" name="Cancel" onclick="window.location='/admin/categories_list'">Cancel</button>
				</div>
			</section>
		</form>

		<? AdminEditSubCategories($dataarray['cid']); ?>

		<form method="POST" action="/admin/categories_list" enctype="multipart/form-data">
			<input type="hidden" name="function" value="add_sub_category">
			<input type="hidden" name="form_cid" value="<?= $dataarray['cid']; ?>">
			<section class="adminPanel adminPanelAdd">
				<div class="adminPanelHeader">
					<h2 class="adminPanelTitle">Add a sub-category to "<?= $dataarray['category'] ?>"</h2>
				</div>
				<div class="adminField">
					<label for="new_subcategory_name">Sub-Category Name</label>
					<input type="text" name="form_category" id="new_subcategory_name" value="<?= MakeCase(htmlspecialchars(trim(($_REQUEST['form_category'] ?? '')))); ?>">
				</div>
				<div class="adminField">
					<label for="new_subcategory_url">URL</label>
					<input type="text" placeholder="Optional, made from the name" name="form_url" id="new_subcategory_url" value="<?= htmlspecialchars(trim(($_REQUEST['form_url'] ?? ''))); ?>" style="text-transform: lowercase" autocapitalize="none" autocorrect="off">
				</div>
				<div class="adminField">
					<label for="new_subcategory_description">Description</label>
					<input type="text" name="form_description" id="new_subcategory_description" value="<?= htmlspecialchars(trim(($_REQUEST['form_description'] ?? ''))); ?>">
				</div>
				<div class="adminField">
					<span class="adminFieldLabel">Sub-Category Logo</span>
					<div>
						<input name="filesToUpload[]" class="filesToUpload" id="2" type="file" multiple="">
						<span class="adminFieldHint">1024x680 PNG/JPG</span>
					</div>
				</div>
				<div class="adminFormButtons">
					<button type="submit" class="adminButton adminButtonPrimary">Add Sub-Category</button>
				</div>
			</section>
		</form>
	<?
}

function AdminEditSubCategory($dataarray) {
	?>
		<form method="POST" action="/admin/categories_list" enctype="multipart/form-data">
			<input type="hidden" name="function" value="save_subcategory">
			<input type="hidden" name="form_subid" value="<?= $dataarray['subid'] ?>">
			<input type="hidden" name="form_cid" value="<?= $dataarray['parent_cid'] ?>">
			<section class="adminPanel">
				<div class="adminPanelHeader">
					<h2 class="adminPanelTitle">Edit sub-category "<?= $dataarray['subcategory'] ?>"</h2>
					<span class="adminPanelSubtitle">inside "<?= $dataarray['category']; ?>"</span>
				</div>
				<div class="adminField">
					<label for="edit_subcategory_name">Short Name</label>
					<input type="text" name="form_subcategory" id="edit_subcategory_name" value="<?= $dataarray['subcategory']?>">
				</div>
				<div class="adminField">
					<label for="edit_subcategory_description">Nice Description</label>
					<input type="text" name="form_description" id="edit_subcategory_description" value="<?= $dataarray['description'] ?>">
				</div>
				<div class="adminField">
					<label for="edit_subcategory_url">URL</label>
					<input type="text" name="form_url" id="edit_subcategory_url" value="<?= $dataarray['url'] ?>" style="text-transform: lowercase" autocapitalize="none" autocorrect="off">
				</div>
				<div class="adminField">
					<span class="adminFieldLabel">Stored Image</span>
					<div class="adminImagePreview"><img src="/i/sample/<?= $dataarray['image_id'] ?>" alt="<?= $dataarray['image_filename'] ?>"></div>
				</div>
				<div class="adminField">
					<span class="adminFieldLabel">Replace Image</span>
					<div>
						<input name="filesToUpload[]" class="filesToUpload" id="1" type="file" multiple="" accept="image/png">
						<span class="adminFieldHint">1024x680. Now: <?= $dataarray['image_filename'] ?></span>
					</div>
				</div>
				<div class="adminFormButtons">
					<button type="submit" class="adminButton adminButtonPrimary">Update Sub-Category</button>
					<button type="button" class="adminButton" name="Cancel" onclick="window.location='/admin/categories_list'">Cancel</button>
				</div>
			</section>
		</form>
	<?
}

function AdminEditSeason($season_details) {
	?>
		<form method="POST" action="/admin/seasons_list" enctype="multipart/form-data">
			<input type="hidden" name="function" value="save_season">
			<input type="hidden" name="sid" value="<?= (int)$season_details['sid'] ?>">
			<section class="adminPanel">
				<div class="adminPanelHeader">
					<h2 class="adminPanelTitle">Rename "<?= $season_details['season'] ?>"</h2>
				</div>
				<div class="adminField">
					<label for="edit_season_name">Season Name</label>
					<input type="text" name="season" id="edit_season_name" value="<?= $season_details['season'] ?>">
				</div>
				<div class="adminFormButtons">
					<button type="submit" class="adminButton adminButtonPrimary">Update Season</button>
					<button type="button" class="adminButton" name="Cancel" onclick="window.location='/admin/seasons_list'">Cancel</button>
				</div>
			</section>
		</form>
	<?
}

function AdminEditTag($dataarray) {
	?>
		<form method="POST" action="/admin/tags_list" enctype="multipart/form-data">
			<input type="hidden" name="function" value="save_tag">
			<input type="hidden" name="tid" value="<?= $dataarray['tid'] ?>">
			<section class="adminPanel">
				<div class="adminPanelHeader">
					<h2 class="adminPanelTitle">Edit tag "<?= $dataarray['name'] ?>"</h2>
				</div>
				<div class="adminField">
					<label for="edit_tag_name">Tag Name</label>
					<input type="text" name="name" id="edit_tag_name" value="<?= $dataarray['name'] ?>">
				</div>
				<div class="adminFormButtons">
					<button type="submit" class="adminButton adminButtonPrimary">Update Tag</button>
					<button type="button" class="adminButton" name="Cancel" onclick="window.location='/admin/tags_list'">Cancel</button>
				</div>
			</section>
		</form>
	<?
}

function AdminsampleFormNew() {
	?>
  <script type="text/javascript">//<![CDATA[
		var filecounter = 1;
		function makeFileList() {
			var input = document.getElementById(filecounter);
			var ul = document.getElementById("fileList");
			for (var i = 0; i < input.files.length; i++) {
				var li = document.createElement("li");
				li.innerHTML = "(" + filecounter + ") " + input.files[i].name;
				ul.appendChild(li);
			}
			if(!ul.hasChildNodes()) {
				var li = document.createElement("li");
				li.innerHTML = 'No Files Selected';
				ul.appendChild(li);
			}
			add_file_field();
		}
		function add_file_field(){
			var input = document.getElementById(filecounter);
			filecounter = filecounter + 1;
			var container=document.getElementById('file_container');
			var file_field=document.createElement('input');
			file_field.name='filesToUpload[]';
			file_field.type='file';
			file_field.onchange='makeFileList();';
			file_field.setAttribute("onchange", "makeFileList();");
			file_field.id=filecounter;
			file_field.setAttribute("id",filecounter);
			container.appendChild(file_field);
			//var br_field=document.createElement('br');
			//container.appendChild(br_field);
		}
    jQuery(function($) {
      $("#Categories").bsmSelect({
        addItemTarget: 'bottom',
        animate: true,
        highlight: true,
        plugins: [
          $.bsmSelect.plugins.sortable({ axis : 'y', opacity : 0.5 }, { listSortableClass : 'bsmListSortableCustom' }),
          $.bsmSelect.plugins.compatibility()
        ]
      });
      $("#SubCategories").bsmSelect({
        addItemTarget: 'bottom',
        animate: true,
        highlight: true,
        plugins: [
          $.bsmSelect.plugins.sortable({ axis : 'y', opacity : 0.5 }, { listSortableClass : 'bsmListSortableCustom' }),
          $.bsmSelect.plugins.compatibility()
        ]
			});
      $("#Tags").bsmSelect({
        addItemTarget: 'bottom',
        animate: true,
        highlight: true,
        plugins: [
          $.bsmSelect.plugins.sortable({ axis : 'y', opacity : 0.5 }, { listSortableClass : 'bsmListSortableCustom' }),
          $.bsmSelect.plugins.compatibility()
        ]
      });
      $("#Seasons").bsmSelect({
        addItemTarget: 'bottom',
        animate: true,
        highlight: true,
        plugins: [
          $.bsmSelect.plugins.sortable({ axis : 'y', opacity : 0.5 }, { listSortableClass : 'bsmListSortableCustom' }),
          $.bsmSelect.plugins.compatibility()
        ]
			});
    });
  //]]></script>
		<form method="POST" action="/admin/samples/add_new" enctype="multipart/form-data" id="irevform">
			<input type="hidden" name="function" value="add_new">
			<input type="hidden" name="formpage" value="1">
			<input type="hidden" value="irevform" name="<?= ini_get("session.upload_progress.name"); ?>">
			<section class="adminPanel adminPanelAdd">
				<div class="adminPanelHeader">
					<h2 class="adminPanelTitle">Add a new sample</h2>
				</div>
				<!-- photos first: they're the main thing now, CLIP writes the words from them -->
				<div class="adminField">
					<label for="1">Photos and Videos</label>
					<div>
						<div id="file_container"><input name="filesToUpload[]" class="filesToUpload" id="1" type="file" multiple="" onchange="makeFileList();" /></div>
						<span class="adminFieldHint">Photos of ONE thing. Big phone photos are fine: they get resized and turned upright. Leave the words below blank and CLIP guesses them from the photos.</span>
						<ul id="fileList" class="adminFileList"></ul>
					</div>
				</div>
				<div class="adminField">
					<label for="new_sample_name">Real Name</label>
					<input type="text" name="name" id="new_sample_name" placeholder="Blank = CLIP guesses" value="<?= MakeCase(htmlspecialchars(trim(($_REQUEST['name'] ?? '')))); ?>">
				</div>
				<div class="adminField">
					<label for="new_sample_display_name">Display Name</label>
					<div>
						<input type="text" name="display_name" id="new_sample_display_name" placeholder="Optional (auto-generated)" value="<?= MakeCase(htmlspecialchars(trim(($_REQUEST['display_name'] ?? '')))); ?>">
						<span class="adminFieldHint">The obfuscated name, for the white-label trick</span>
					</div>
				</div>
				<div class="adminField">
					<label for="new_sample_slug">One-Line Summary</label>
					<textarea rows="2" name="slug" id="new_sample_slug" placeholder="Blank = CLIP guesses" wrap="virtual"><?= MakeCase(htmlspecialchars(trim(($_REQUEST['slug'] ?? '')))); ?></textarea>
				</div>
				<div class="adminChecks">
					<label class="adminCheck" title="Active, available for gigs">
						<input type="checkbox" name="is_active" id="is_active" <?= /* I want default first page to be checked */ (($_REQUEST['is_active'] ?? '') || !(($_REQUEST['formpage'] ?? '')))? 'CHECKED ' : ''; ?>>
						Active
					</label>
					<label class="adminCheck" title="Sample listed in search results">
						<input type="checkbox" name="is_searchable" id="is_searchable" <?= /* I want default first page to be checked */ (($_REQUEST['is_searchable'] ?? '') || !(($_REQUEST['formpage'] ?? '')))? 'CHECKED ' : ''; ?>>
						Searchable
					</label>
					<label class="adminCheck" title="Sample is highlighted on Home Page and Category Listings">
						<input type="checkbox" name="is_highlighted" id="is_highlighted" <?= /* I want default first page to be unchecked */ (($_REQUEST['is_highlighted'] ?? ''))? 'CHECKED ' : ''; ?>>
						Featured <span class="adminFieldHint">(home page and category lists)</span>
					</label>
				</div>
				<div class="adminField">
					<label for="new_sample_bio">Bio</label>
					<textarea rows="8" name="bio" id="new_sample_bio" placeholder="Blank = CLIP lists everything it guessed, for you to edit down" wrap="virtual"><?= htmlspecialchars(trim(($_REQUEST['bio'] ?? ''))); ?></textarea>
				</div>
				<div class="adminField">
					<label for="Categories">Categories</label>
					<div class="adminMultiSelect">
						<select id="Categories" multiple="multiple" name="categories[]" title="Add a category..." class="sminit">
							<?= AdminSelectCategories(); ?>
						</select>
					</div>
				</div>
				<div class="adminField">
					<label for="Tags">Tags</label>
					<div class="adminMultiSelect">
						<select id="Tags" multiple="multiple" name="tags[]" title="Add a tag..." class="sminit">
							<?= AdminSelectTags(); ?>
						</select>
					</div>
				</div>
				<div class="adminField">
					<label for="Seasons">Seasons</label>
					<div class="adminMultiSelect">
						<select id="Seasons" multiple="multiple" name="seasons[]" title="Add a season..." class="sminit">
							<?= AdminSelectSeasons(); ?>
						</select>
					</div>
				</div>
				<div class="adminFormButtons">
					<button type="submit" class="adminButton adminButtonPrimary">Save New Sample</button>
				</div>
				<div id="bar_blank">
					<div id="status"></div>
					<div id="bar_color"></div>
				</div>
			</section>
		</form>
		<script type="text/javascript" src="/templates/js/upload.js"></script>
	<?
}

function AdminsampleFormSingle($sampleinfo) {
	?>
	<script type="text/javascript" src="/templates/js/jwplayer/jwplayer.js"></script>
  <script type="text/javascript">//<![CDATA[
		var filecounter = 1;
		function makeFileList() {
			var input = document.getElementById(filecounter);
			var ul = document.getElementById("fileList");
			for (var i = 0; i < input.files.length; i++) {
				var li = document.createElement("li");
				li.innerHTML = "(" + filecounter + ") " + input.files[i].name;
				ul.appendChild(li);
			}
			if(!ul.hasChildNodes()) {
				var li = document.createElement("li");
				li.innerHTML = 'Upload Additional Media';
				ul.appendChild(li);
			}
			add_file_field();
		}
		function add_file_field(){
			var input = document.getElementById(filecounter);
			filecounter = filecounter + 1;
			var container=document.getElementById('file_container');
			var file_field=document.createElement('input');
			file_field.name='filesToUpload[]';
			file_field.type='file';
			file_field.onchange='makeFileList();';
			file_field.setAttribute("onchange", "makeFileList();");
			file_field.id=filecounter;
			file_field.setAttribute("id",filecounter);
			container.appendChild(file_field);
			//var br_field=document.createElement('br');
			//container.appendChild(br_field);
		}
    jQuery(function($) {
      $("#Categories").bsmSelect({
        addItemTarget: 'bottom',
        animate: true,
        highlight: true,
        plugins: [
          $.bsmSelect.plugins.sortable({ axis : 'y', opacity : 0.5 }, { listSortableClass : 'bsmListSortableCustom' }),
          $.bsmSelect.plugins.compatibility()
        ]
      });
      $("#SubCategories").bsmSelect({
        addItemTarget: 'bottom',
        animate: true,
        highlight: true,
        plugins: [
          $.bsmSelect.plugins.sortable({ axis : 'y', opacity : 0.5 }, { listSortableClass : 'bsmListSortableCustom' }),
          $.bsmSelect.plugins.compatibility()
        ]
      });
      $("#Tags").bsmSelect({
        addItemTarget: 'bottom',
        animate: true,
        highlight: true,
        plugins: [
          $.bsmSelect.plugins.sortable({ axis : 'y', opacity : 0.5 }, { listSortableClass : 'bsmListSortableCustom' }),
          $.bsmSelect.plugins.compatibility()
        ]
      });
      $("#Seasons").bsmSelect({
        addItemTarget: 'bottom',
        animate: true,
        highlight: true,
        plugins: [
          $.bsmSelect.plugins.sortable({ axis : 'y', opacity : 0.5 }, { listSortableClass : 'bsmListSortableCustom' }),
          $.bsmSelect.plugins.compatibility()
        ]
			});
    });
		HEIGHTS = [];
		function getheight(images, width) {
			width -= images.length * 5;
			var h = 0;
			for (var i = 0; i < images.length; ++i) {
				h += $(images[i]).data('width') / $(images[i]).data('height');
			}
			return width / h;
		}
		function setheight(images, height) {
			HEIGHTS.push(height);
			for (var i = 0; i < images.length; ++i) {
				$(images[i]).css({
					width: height * $(images[i]).data('width') / $(images[i]).data('height'),
					height: height
				});
				$(images[i]).attr('src', $(images[i]).attr('src').replace(/w[0-9]+-h[0-9]+/, 'w' + $(images[i]).width() + '-h' + $(images[i]).height()));
			}
		}
		function resize(images, width) {
		  setheight(images, getheight(images, width));
		}
		function run(max_height) {
			// pack the photos into the width of their panel, not the whole window:
			// the window guess made the last photo of each row hang off the edge on a phone
			var size = $('.AdminImagesPreviewContainer').first().width() - 4;
			var n = 0;
			var images = $('img');
			w: while (images.length > 0) {
				for (var i = 1; i < images.length + 1; ++i) {
					var slice = images.slice(0, i);
					var h = getheight(slice, size);
					if (h < max_height) {
						setheight(slice, h);
						n++;
						images = images.slice(i);
						continue w;
					}
				}
				setheight(slice, Math.min(max_height, h));
				n++;
				break;
			}
			console.log(n);
		}
		window.addEventListener('resize', function () { run(205); });
		$(function () { run(205); });

		//]]></script>
		<form method="POST" action="/admin/samples/edit/<?= $sampleinfo['oid']; ?>" enctype="multipart/form-data" id="irevform" name="edit<?= $sampleinfo['oid']; ?>">
			<input type="hidden" name="oid" value="<?= $sampleinfo['oid']; ?>">
			<input type="hidden" name="function" value="edit">
			<input type="hidden" value="irevform" name="<?= ini_get("session.upload_progress.name"); ?>">
			<section class="adminPanel">
				<div class="adminPanelHeader">
					<h2 class="adminPanelTitle"><a href="<?= CurServerUrl(); ?>sample/<?= $sampleinfo['url']; ?>" title="See the public page" class="adminPanelTitleRealName"><?= $sampleinfo['name'] ?></a></h2>
					<span class="adminPanelSubtitle">Last updated <?= nicetime($sampleinfo['last_updated']) ?></span>
				</div>
				<!--
					Save comes before Delete on purpose: pressing Enter in a text box
					"clicks" the first submit button in the form, and that had better
					not be the one that deletes things.
				-->
				<div class="adminFormButtons">
					<button type="submit" name="executeButton" value="update" class="adminButton adminButtonPrimary" title="Update <?= $sampleinfo['name']; ?>">Save Updates</button>
					<button type="submit" name="executeButton" value="delete" class="adminButton adminButtonDanger" title="Delete <?= $sampleinfo['name']; ?> (asks first)">Delete Sample</button>
				</div>
				<div class="adminChecks">
					<label class="adminCheck" title="sample is Active">
						<input type="checkbox" id="is_active" name="is_active" <?= ($sampleinfo['is_active']?'CHECKED ':'') ?>>
						Active
					</label>
					<label class="adminCheck" title="Can be found in Search">
						<input type="checkbox" id="is_searchable" name="is_searchable" <?= ($sampleinfo['is_searchable']?'CHECKED ':'') ?>>
						Searchable
					</label>
					<label class="adminCheck" title="Highlighted as a Featured sample">
						<input type="checkbox" id="is_highlighted" name="is_highlighted" <?= ($sampleinfo['is_highlighted']?'CHECKED ':'') ?>>
						Featured
					</label>
					<label class="adminCheck" title="Enforce hiding the sample's Full Name">
						<input type="checkbox" id="use_display_name" name="use_display_name" <?= ($sampleinfo['use_display_name']?'CHECKED ':'') ?>>
						Use Display Name
					</label>
				</div>
				<div class="adminField">
					<label for="edit_sample_name">Real Name</label>
					<input type="text" name="name" id="edit_sample_name" value="<?= $sampleinfo['name']; ?>">
				</div>
				<div class="adminField">
					<label for="edit_sample_display_name">Display Name</label>
					<input type="text" name="display_name" id="edit_sample_display_name" value="<?= $sampleinfo['display_name']; ?>">
				</div>
				<div class="adminField">
					<label for="edit_sample_slug">Summary / Slug</label>
					<textarea rows="2" name="slug" id="edit_sample_slug" wrap="virtual"><?= $sampleinfo['slug']; ?></textarea>
				</div>
				<div class="adminField">
					<label for="edit_sample_bio">Bio</label>
					<textarea rows="8" name="bio" id="edit_sample_bio" wrap="virtual"><?= $sampleinfo['bio']; ?></textarea>
				</div>
				<div class="adminField">
					<label for="edit_sample_url">URL</label>
					<input type="text" name="form_url" id="edit_sample_url" value="<?= $sampleinfo['url']; ?>" autocapitalize="none" autocorrect="off">
				</div>
				<div class="adminField">
					<label for="edit_sample_alt_url">Alt-URL</label>
					<input type="text" name="alt_url" id="edit_sample_alt_url" value="<?= $sampleinfo['alt_url']; ?>" autocapitalize="none" autocorrect="off">
				</div>
				<div class="adminField">
					<label for="Categories">Categories</label>
					<div class="adminMultiSelect">
						<select id="Categories" multiple="multiple" name="categories[]" title="Add a category..." class="sminit">
							<?= AdminSelectCategories($sampleinfo['oid']); ?>
						</select>
					</div>
				</div>

				<? AdminSelectSubCategories($sampleinfo); ?>

				<div class="adminField">
					<label for="Tags">Tags</label>
					<div class="adminMultiSelect">
						<select id="Tags" multiple="multiple" name="tags[]" title="Add a tag..." class="sminit">
							<?= AdminSelectTags($sampleinfo['oid']); ?>
						</select>
					</div>
				</div>
				<div class="adminField">
					<label for="Seasons">Seasons</label>
					<div class="adminMultiSelect">
						<select id="Seasons" multiple="multiple" name="seasons[]" title="Add a season..." class="sminit">
							<?= AdminSelectSeasons($sampleinfo['oid']); ?>
						</select>
					</div>
				</div>
				<div class="adminFormButtons">
					<button type="submit" name="executeButton" value="update" class="adminButton adminButtonPrimary">Save Updates</button>
				</div>
			</section>

			<section class="adminPanel">
				<div class="adminPanelHeader">
					<h2 class="adminPanelTitle">Media for <?= $sampleinfo['name']; ?></h2>
				</div>
				<div class="adminField">
					<label for="1">Add Photos and Videos</label>
					<div>
						<div id="file_container"><input name="filesToUpload[]" class="filesToUpload" id="1" type="file" multiple="" onchange="makeFileList();" /></div>
						<ul id="fileList" class="adminFileList"></ul>
					</div>
				</div>
				<div id="bar_blank">
					<div id="status"></div>
					<div id="bar_color"></div>
				</div>
				<script type="text/javascript" src="/templates/js/upload.js"></script>
				<div class="AdminImagesPreviewContainer">
					<?= ShowPhotoArray($sampleinfo['media']); ?>
				</div>
				<div class="AdminImagesPreviewContainer">
					<?= PrepareVideoPlayer($sampleinfo); ?>
				</div>
				<div class="adminFormButtons">
					<button type="submit" name="executeButton" value="update" class="adminButton adminButtonPrimary">Save Updates</button>
				</div>
			</section>
		</form>

	<?
}

function AdminVideoPreviewChooser($sampleinfo) {
	// using div in-line style background to show the images because img tag is taken by ShowPhotoArray()'s css image array resizer
	?>
		<div class="AdminVideoPreviewChooserContainer">
		<? for ($i = 1; $i < 5; $i++) { ?>
			<div class="CheckBoxImageContainer">
				<label for="<?= $sampleinfo['media']['mid'] . "-$i"; ?>"><div class="AdminVideoPreviewChooserImage" style="background: url('/i/sample/<?= $sampleinfo['media']['fileid']. "-$i.jpg"; ?>'); background-size:contain; background-repeat: no-repeat;"></div></label>
				<div class="CheckBoxImage"><input type="radio" name="radio[<?= $sampleinfo['media']['mid']; ?>]" id="<?= $sampleinfo['media']['mid']."-$i"; ?>" value="<?= $i; ?>"></div>
			</div>
		<? } ?>
		</div>
		<div class="VideoSize">
			<strong><?= $sampleinfo['media']['realdimensions']; ?></strong>
			(<?= $sampleinfo['media']['aspectratio']; ?>)
			<select name="videoaction[<?= $sampleinfo['media']['mid']; ?>]" <?= ((int)$sampleinfo['media']['viewable'] === 0)? "style='background-color: #FFBABA;' " : ""; ?>>
				<option value='' selected="SELECTED" disabled="disabled">Actions</option>
				<?= ((int)$sampleinfo['media']['viewable'] === 1)? '<option value="0">Make Hidden</option>' : '<option value="1">Make Visible</option>'; ?>
				<option value="delete">Remove Video</option>
			</select>
		</div>
		<div class="clear"></div>
	<?
}
