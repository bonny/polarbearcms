
```
polarbear_menu($rootPageID = null, $options = null)

	$defaults = array(
		'includeRoot' => false,
		'rootPageID' => $rootPageID,
		'openOnlyActive' => true,
		'selectedPage' => $_GET['polarbear-page'],
		'defaultFormat' => "<a href='{\$href}'>{\$titleNav}</a>",
		'defaultSort' => 'prio',
		'defaultSortDirection' => 'desc',
		'defaultLimitStart' => 0,
		'defaultLimitCount' => null, // empty val interpreted as "all"
		'maxDepth' => null, // how deep can the menu be. default is infinitive. a maxDepth of "1" would only show the root
		'rootULClass' => "polarbear-nav" // class that the root ul should get. great for styling menues different (navigation vs. sitemap for example)
	);

```