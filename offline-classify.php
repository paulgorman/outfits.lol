#!/usr/bin/env php
<?php
/*
 *  offline-classify.php - Presence's Outfits Gizmo, the "what the heck is this" part.
 *
 *  CLIP looks at a sample's photos and fills in whatever was left blank:
 *  name, summary, bio, category, sub-category, tags. It picks from YOUR
 *  categories and tags in the database, and never overwrites anything you
 *  entered. All the thinking lives in shared.php (look for "CLIP FILLS IN
 *  THE BLANKS"); this file loads CLIP, talks to the database, and prints.
 *
 *  Usage:
 *    php offline-classify.php --sample 7            fill in sample 7's blanks
 *    php offline-classify.php --sample 7 --dry-run  show what it WOULD fill in, change nothing
 *    php offline-classify.php --mysteries           every sample still named "Mystery Thing ..."
 *                                                   (cron-able: cleans up after any upload where CLIP fell over)
 *    php offline-classify.php photo.jpg [...]       just guess at photo files, change nothing
 *    add --json for machine-readable output (the upload page uses --sample N --json)
 *    add --model NAME to try a different CLIP brain (see $clip_model_nicknames below)
 *
 *  Model bake-off, nothing saved:
 *    php offline-classify.php --model base    --sample 7 --dry-run
 *    php offline-classify.php --model fashion --sample 7 --dry-run
 *
 *  It runs as its own process, never inside a web request: CLIP wants
 *  ~550MB and web PHP gets 256MB. Model load is ~1 second, then under a
 *  second per photo.
 *
 *  Setup (once, in this directory):
 *    composer require codewithkyrian/transformers
 *  First run downloads the CLIP model (a few hundred MB) from Hugging Face.
 *  Go get a botsnack while it does that.
 */

use function Codewithkyrian\Transformers\Pipelines\pipeline;

// The model cache (.transformers-cache) and db.php are found relative to the
// current directory. The upload page starts us from who-knows-where, so stand here first.
chdir(__DIR__);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/shared.php';

date_default_timezone_set('America/Los_Angeles');



/*
 *  ConnectToDatabaseFromCommandLine
 *  The web pages connect in Init(), but Init() also starts a browser session,
 *  which makes no sense in a terminal. So: just the database part.
 */
function ConnectToDatabaseFromCommandLine() {
	global $conn;
	global $dirlocation;

	$dirlocation = __DIR__; // db.php may override it, same as on the web side
	require __DIR__ . '/db.php'; // $host, $user, $pass, $db

	mysqli_report(MYSQLI_REPORT_OFF);
	$conn = mysqli_connect($host, $user, $pass, $db, 3306);

	if (!$conn) {
		fwrite(STDERR, "can't reach the database: " . mysqli_connect_error() . "\n");
		exit(1);
	}
}



/*
 *  GetMysterySampleIds
 *  Every sample still wearing its stand-in name: CLIP never got to it.
 */
function GetMysterySampleIds() {
	global $conn;

	$mystery_sample_ids = array();
	$name_pattern = GetMysteryThingNamePrefix() . "%";

	$mystery_query = $conn->prepare(
		"SELECT `oid`
		 FROM `samples`
		 WHERE `name` LIKE ?
		 ORDER BY `oid`"
	);
	$mystery_query->bind_param("s", $name_pattern);
	$mystery_query->execute();
	$mystery_rows = $mystery_query->get_result();
	while ($mystery_row = $mystery_rows->fetch_assoc()) {
		$mystery_sample_ids[] = $mystery_row['oid'];
	}
	$mystery_query->close();

	return $mystery_sample_ids;
}



/*
 *  ShowClipDescriptionForHumans
 *  Pretty-prints every menu's guesses, then what got (or would get) filled in.
 */
function ShowClipDescriptionForHumans($what_was_looked_at, $clip_description, $what_got_filled_in, $seconds_it_took) {

	printf("\n== %s  (%.1fs)\n", $what_was_looked_at, $seconds_it_took);

	foreach ($clip_description['guesses'] as $menu_name => $guesses_for_this_menu) {
		$guesses_as_readable_text = array();
		foreach (array_slice($guesses_for_this_menu, 0, 3) as $single_guess) {
			$guesses_as_readable_text[] = sprintf('%s %.0f%%', $single_guess['label'], $single_guess['score'] * 100);
		}
		printf("  %-12s %s\n", $menu_name . ':', implode('  |  ', $guesses_as_readable_text));
	}

	printf("  %-12s %s\n", 'summary:', $clip_description['summary']);

	if (count($what_got_filled_in) == 0) {
		printf("  %-12s %s\n", 'filled in:', 'nothing (everything was already filled in)');
	} else {
		printf("  %-12s %s\n", 'filled in:', implode('; ', $what_got_filled_in));
	}
}



/*
 *  WhatWouldGetFilledIn
 *  For --dry-run: the same kind of list SaveClipDescriptionIntoSample()
 *  returns, without writing anything.
 */
function WhatWouldGetFilledIn($clip_description) {
	$what_would_get_filled_in = array();
	foreach ($clip_description['what_to_write'] as $field_name => $new_value) {
		if (is_array($new_value)) {
			$what_would_get_filled_in[] = "$field_name " . implode(',', $new_value);
		} else {
			$what_would_get_filled_in[] = $field_name;
		}
	}
	return $what_would_get_filled_in;
}



/*
 *  PutBackOurModelFixes
 *  Some models download with config files TransformersPHP can't read (see
 *  clip-model-fixes/README.md). The fixed copies live in clip-model-fixes/,
 *  and this copies them into .transformers-cache/ before the model loads.
 *  The cache is "just a cache" and could get wiped any day; the library
 *  only downloads files that AREN'T there yet, so ours win, every time.
 */
function PutBackOurModelFixes($clip_model_name) {
	$folder_with_our_fixes = __DIR__ . "/clip-model-fixes/" . $clip_model_name;
	$folder_in_the_cache = __DIR__ . "/.transformers-cache/" . $clip_model_name;

	if (!is_dir($folder_with_our_fixes)) {
		return; // most models download just fine
	}

	if (!is_dir($folder_in_the_cache)) {
		mkdir($folder_in_the_cache, 0775, true);
	}

	foreach (scandir($folder_with_our_fixes) as $fixed_file_name) {
		$fixed_file_path = $folder_with_our_fixes . "/" . $fixed_file_name;
		if (!is_file($fixed_file_path)) {
			continue; // ".", "..", and any folders
		}

		$cached_file_path = $folder_in_the_cache . "/" . $fixed_file_name;
		$fixed_file_contents = file_get_contents($fixed_file_path);

		// only write when it's missing or different, so the cache file's
		// date tells you when a fix last had to be put back
		if (!is_file($cached_file_path) || file_get_contents($cached_file_path) !== $fixed_file_contents) {
			file_put_contents($cached_file_path, $fixed_file_contents);
		}
	}
}



/*  Which CLIP brain to use. Short nicknames so the bake-off doesn't need
 *  Hugging Face names typed out. Anything not in this list gets passed
 *  straight through, so a full "someone/some-model" name works too.
 *
 *  fashion - THE DEFAULT since 2026-11-11. Base-sized, but retrained on a
 *            fashion retailer's catalog, so it knows midi from maxi, and it
 *            sees black. ~3-7s per upload, ~1.35GB of RAM (full-size only).
 *  base    - the old default. Small, fast, a generalist ("skirt" == "giraffe").
 *
 *  (Xenova/clip-vit-large-patch14 lost the bake-off too: better than base,
 *  worse than fashion, and 6-34 seconds per sample. Presence will never
 *  wait 20 seconds for a skirt. Dropped 2026-11-11.)
 */
$clip_model_nicknames = array(
	'base'    => 'Xenova/clip-vit-base-patch32',
	'fashion' => 'patrickjohncyh/fashion-clip',
);

// Change this line when the bake-off has a winner.
$clip_model_to_use = $clip_model_nicknames['fashion']; // 2026-11-11: fashion won the bake-off (it sees black!)

// TransformersPHP loads the shrunk-down "quantized" copy of a model by default
// (onnx/model_quantized.onnx). Some models only come full-size (onnx/model.onnx),
// and asking them for the small one is a "Could not instantiate model" crash.
$clip_models_that_only_come_full_size = array(
	'patrickjohncyh/fashion-clip',
);



/* ==== main program ======================================================= */

$command_line_arguments = array_slice($argv, 1);

$output_as_json = false;
$dry_run = false;
$do_every_mystery = false;
$sample_ids_to_classify = array();
$photo_files_to_classify = array();

// read the arguments one at a time; "--sample" eats the number after it
$argument_position = 0;
while ($argument_position < count($command_line_arguments)) {
	$this_argument = $command_line_arguments[$argument_position];

	if ($this_argument === '--json') {
		$output_as_json = true;
	} elseif ($this_argument === '--dry-run') {
		$dry_run = true;
	} elseif ($this_argument === '--mysteries') {
		$do_every_mystery = true;
	} elseif ($this_argument === '--model') {
		// "--model" eats the word after it, same trick as "--sample"
		$argument_position++;
		$requested_model = $command_line_arguments[$argument_position] ?? '';
		if (isset($clip_model_nicknames[$requested_model])) {
			$clip_model_to_use = $clip_model_nicknames[$requested_model];
		} elseif ($requested_model !== '') {
			$clip_model_to_use = $requested_model;
		}
	} elseif ($this_argument === '--sample') {
		$argument_position++;
		$sample_ids_to_classify[] = (int)($command_line_arguments[$argument_position] ?? 0);
	} else {
		$photo_files_to_classify[] = $this_argument;
	}

	$argument_position++;
}

if (count($sample_ids_to_classify) == 0 && count($photo_files_to_classify) == 0 && !$do_every_mystery) {
	fwrite(STDERR, "usage: {$argv[0]} [--json] [--dry-run] [--model fashion|base] --sample N | --mysteries | photo.jpg [...]\n");
	exit(1);
}

ConnectToDatabaseFromCommandLine();

if ($do_every_mystery) {
	foreach (GetMysterySampleIds() as $mystery_sample_id) {
		$sample_ids_to_classify[] = $mystery_sample_id;
	}
	if (count($sample_ids_to_classify) == 0 && count($photo_files_to_classify) == 0) {
		if (!$output_as_json) {
			print "No mystery things. The closet is fully labeled.\n";
		}
		exit(0);
	}
}


// Load CLIP. This is the fopen() of the whole thing: slow once, then cheap.
// A new model's first run downloads it from Hugging Face: botsnack time again.
$time_model_load_started = microtime(true);
PutBackOurModelFixes($clip_model_to_use);
$use_the_quantized_copy = true;
if (in_array($clip_model_to_use, $clip_models_that_only_come_full_size)) {
	$use_the_quantized_copy = false;
}
$clip_classifier = pipeline('zero-shot-image-classification', $clip_model_to_use, $use_the_quantized_copy);
if (!$output_as_json) {
	printf("model %s loaded in %.1fs\n", $clip_model_to_use, microtime(true) - $time_model_load_started);
}

$everything_for_json = array();
$something_went_wrong = false;


// ---- samples in the database ----
foreach ($sample_ids_to_classify as $sample_id) {
	$time_this_one_started = microtime(true);

	$sample_facts = GetSampleFactsForClip($sample_id);
	if ($sample_facts === NULL) {
		fwrite(STDERR, "there's no sample number $sample_id, skipping it\n");
		$something_went_wrong = true;
		continue;
	}
	if (count($sample_facts['photo_paths']) == 0) {
		fwrite(STDERR, "sample $sample_id has no photos to look at, skipping it\n");
		$something_went_wrong = true;
		continue;
	}

	$clip_description = DescribeSampleWithClip($clip_classifier, $sample_facts);

	if ($dry_run) {
		$what_got_filled_in = WhatWouldGetFilledIn($clip_description);
	} else {
		$what_got_filled_in = SaveClipDescriptionIntoSample($clip_description);
	}

	if ($output_as_json) {
		$everything_for_json[] = array(
			'oid' => $sample_id,
			'summary' => $clip_description['summary'],
			'filled_in' => $what_got_filled_in,
			'dry_run' => $dry_run,
			'model' => $clip_model_to_use,
			'guesses' => $clip_description['guesses'],
		);
	} else {
		$what_was_looked_at = "sample $sample_id: " . $sample_facts['name'];
		if ($dry_run) {
			$what_was_looked_at .= "  (DRY RUN, nothing saved)";
		}
		ShowClipDescriptionForHumans($what_was_looked_at, $clip_description, $what_got_filled_in, microtime(true) - $time_this_one_started);
	}
}


// ---- loose photo files: guess only, never saved ----
if (count($photo_files_to_classify) > 0) {
	$readable_photo_files = array();
	foreach ($photo_files_to_classify as $photo_file) {
		if (is_readable($photo_file)) {
			$readable_photo_files[] = $photo_file;
		} else {
			fwrite(STDERR, "can't read $photo_file, skipping it\n");
			$something_went_wrong = true;
		}
	}

	if (count($readable_photo_files) > 0) {
		$time_photos_started = microtime(true);
		// all the files are photos of ONE thing, guessed together
		$clip_description = DescribeSampleWithClip($clip_classifier, GetBlankSampleFactsForPhotos($readable_photo_files));
		$what_would_get_filled_in = WhatWouldGetFilledIn($clip_description);

		if ($output_as_json) {
			$everything_for_json[] = array(
				'photos' => $readable_photo_files,
				'summary' => $clip_description['summary'],
				'filled_in' => $what_would_get_filled_in,
				'dry_run' => true,
				'model' => $clip_model_to_use,
				'guesses' => $clip_description['guesses'],
			);
		} else {
			ShowClipDescriptionForHumans(implode(', ', $readable_photo_files) . "  (photo files, nothing saved)", $clip_description, $what_would_get_filled_in, microtime(true) - $time_photos_started);
		}
	}
}


if ($output_as_json) {
	// one sample (what the upload page asks for): just that one, not a list of one
	if (count($everything_for_json) == 1) {
		echo json_encode($everything_for_json[0], JSON_PRETTY_PRINT), "\n";
	} else {
		echo json_encode($everything_for_json, JSON_PRETTY_PRINT), "\n";
	}
}

if ($something_went_wrong) {
	exit(1);
}
exit(0);
