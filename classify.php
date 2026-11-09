#!/usr/bin/env php
<?php
/*
 *  classify.php - Presence's Outfits Gizmo, the "what the heck is this" part.
 *
 *  Point it at a photo of a clothing thing. CLIP looks at it and guesses.
 *  It's levenshtein() for pictures: we hand it every possible answer,
 *  it scores how well each one fits the image, highest score wins.
 *
 *  Setup (once, in this directory):
 *    composer require codewithkyrian/transformers
 *  First run downloads the CLIP model (a few hundred MB) from Hugging Face.
 *  Go get a botsnack while it does that.
 *
 *  Usage:
 *    ./classify.php photo.jpg [more.jpg ...]
 *    ./classify.php --json photo.jpg       (machine-readable, for the web app later)
 */

require __DIR__ . '/vendor/autoload.php';

use function Codewithkyrian\Transformers\Pipelines\pipeline;


/* ---- The menus. Thesaurus away. -----------------------------------------
 *  Each menu is scored on its own. Words inside one menu compete with each
 *  other, so "jeans" and "pants" will split the vote. That's fine.
 *  The top pick from each menu becomes the prefill on the upload form.
 */
$clothing_guess_menus = array(
	'type' => array(
		'pants', 'jeans', 'shorts', 'long skirt', 'short skirt', 'tulle skirt',
		't-shirt', 'long-sleeve shirt', 'button-up shirt', 'tank top',
		'sweater', 'hoodie', 'jacket', 'dress', 'leggings',
	),
	'color' => array(
		'black', 'white', 'gray', 'blue', 'navy blue', 'light blue',
		'green', 'olive green', 'brown', 'tan', 'red', 'burgundy',
		'pink', 'purple', 'yellow', 'orange', 'teal', 'multicolored',
	),
	'pattern' => array(
		'solid color', 'striped', 'plaid', 'denim', 'polka dot',
		'graphic print', 'tie-dye', 'sequined', 'lace', 'shiny',
	),
	'condition' => array(
		'brand new', 'worn-in', 'faded', 'ripped', 'dressy', 'casual',
	),
);

/*  How each menu's words get wrapped into a sentence.
 *  CLIP learned from photo captions off the internet, so it likes
 *  "a photo of blue clothing" way more than it likes just "blue".
 *  The %s is where the menu word goes.
 */
$caption_sentence_template_for_each_menu = array(
	'type'      => 'a photo of %s',
	'color'     => 'a photo of %s clothing',
	'pattern'   => 'a photo of %s clothing',
	'condition' => 'a photo of %s clothing',
);

// how many runner-up guesses to show per menu
$how_many_guesses_to_keep_per_menu = 3;
// -------------------------------------------------------------------------



/*
 *  SortGuessesHighestScoreFirst
 *  usort() wants a function to compare two things. Here it is, with a name.
 *  Bigger score goes first.
 */
function SortGuessesHighestScoreFirst($first_guess, $second_guess) {
	if ($first_guess['score'] == $second_guess['score']) {
		return 0;
	}

	if ($first_guess['score'] > $second_guess['score']) {
		return -1;
	}
	return 1;
}



/*
 *  GuessClothingDescriptionFromPhoto
 *  Runs one photo through every menu and returns the best guesses, like:
 *    'type'  => [ ['label' => 'jeans', 'score' => 0.71], ... ],
 *    'color' => [ ['label' => 'blue',  'score' => 0.58], ... ],
 *  $clip_classifier is the already-loaded pipeline. Loading it is the slow
 *  part, so we load it ONCE out in the main program and pass it in here.
 */
function GuessClothingDescriptionFromPhoto($clip_classifier, $photo_filename, $clothing_guess_menus, $caption_sentence_template_for_each_menu, $how_many_guesses_to_keep_per_menu) {

	$this_photo_description = array();

	foreach ($clothing_guess_menus as $menu_name => $menu_words) {

		// Build full caption sentences, and remember which plain word each
		// sentence came from, so we can hand back "blue" and not
		// "a photo of blue clothing".
		$plain_word_for_each_caption = array();

		foreach ($menu_words as $menu_word) {
			$caption_sentence = sprintf($caption_sentence_template_for_each_menu[$menu_name], $menu_word);
			$plain_word_for_each_caption[$caption_sentence] = $menu_word;
		}

		$candidate_captions_for_this_menu = array_keys($plain_word_for_each_caption);


		// The actual magic. One line. Everything else is bookkeeping.
		$every_guess_for_this_menu = $clip_classifier($photo_filename, $candidate_captions_for_this_menu);


		usort($every_guess_for_this_menu, 'SortGuessesHighestScoreFirst');

		$best_guesses_for_this_menu = array_slice($every_guess_for_this_menu, 0, $how_many_guesses_to_keep_per_menu);


		foreach ($best_guesses_for_this_menu as $single_guess) {

			$caption_that_won = $single_guess['label'];

			// translate the sentence back to the plain menu word
			if (isset($plain_word_for_each_caption[$caption_that_won])) {
				$plain_word_that_won = $plain_word_for_each_caption[$caption_that_won];
			} else {
				$plain_word_that_won = $caption_that_won;
			}

			$this_photo_description[$menu_name][] = array(
				'label' => $plain_word_that_won,
				'score' => round($single_guess['score'], 3),
			);
		}
	}

	return $this_photo_description;
}



/*
 *  ShowPhotoDescriptionForHumans
 *  Pretty-prints one photo's guesses to the terminal.
 */
function ShowPhotoDescriptionForHumans($photo_filename, $this_photo_description, $seconds_it_took) {

	printf("\n== %s  (%.1fs)\n", $photo_filename, $seconds_it_took);

	foreach ($this_photo_description as $menu_name => $guesses_for_this_menu) {

		$guesses_as_readable_text = array();

		foreach ($guesses_for_this_menu as $single_guess) {
			$score_as_percent = $single_guess['score'] * 100;
			$guesses_as_readable_text[] = sprintf('%s %.0f%%', $single_guess['label'], $score_as_percent);
		}

		printf("  %-10s %s\n", $menu_name . ':', implode('  |  ', $guesses_as_readable_text));
	}

	// The one-line prefill the upload form would get.
	// Top pick from each menu, smooshed together like a human would say it.
	printf("  guess:     %s %s, %s, %s\n",
		$this_photo_description['color'][0]['label'],
		$this_photo_description['type'][0]['label'],
		$this_photo_description['pattern'][0]['label'],
		$this_photo_description['condition'][0]['label']
	);
}



/* ==== main program ======================================================= */

$command_line_arguments = array_slice($argv, 1);

// Did they ask for --json? If so, pull it out of the list of filenames.
$output_as_json = false;
$position_of_json_flag = array_search('--json', $command_line_arguments);

if ($position_of_json_flag !== false) {
	$output_as_json = true;
	unset($command_line_arguments[$position_of_json_flag]);
}

$photo_filenames_to_classify = $command_line_arguments;

if (count($photo_filenames_to_classify) == 0) {
	fwrite(STDERR, "usage: {$argv[0]} [--json] image.jpg [...]\n");
	exit(1);
}


// Load CLIP. This is the fopen() of the whole thing: slow once, then cheap.
// default model: Xenova/clip-vit-base-patch32
$time_model_load_started = microtime(true);

$clip_classifier = pipeline('zero-shot-image-classification');

if (!$output_as_json) {
	printf("model loaded in %.1fs\n", microtime(true) - $time_model_load_started);
}


$every_photo_description = array();

foreach ($photo_filenames_to_classify as $photo_filename) {

	if (!is_readable($photo_filename)) {
		fwrite(STDERR, "can't read $photo_filename, skipping it\n");
		continue;
	}

	$time_this_photo_started = microtime(true);

	$this_photo_description = GuessClothingDescriptionFromPhoto(
		$clip_classifier,
		$photo_filename,
		$clothing_guess_menus,
		$caption_sentence_template_for_each_menu,
		$how_many_guesses_to_keep_per_menu
	);

	$seconds_this_photo_took = microtime(true) - $time_this_photo_started;

	$every_photo_description[$photo_filename] = $this_photo_description;

	if (!$output_as_json) {
		ShowPhotoDescriptionForHumans($photo_filename, $this_photo_description, $seconds_this_photo_took);
	}
}


if ($output_as_json) {
	echo json_encode($every_photo_description, JSON_PRETTY_PRINT), "\n";
}
