<?php

/**
 * Converts an English plural word to its singular form.
 *
 * @param string $word The plural word to singularize.
 * @return string The singularized word.
 */
function singularize(string $word): string
{
    $original = $word;
    $lower = strtolower($word);

    // 1. Words that are the same in singular and plural
    $uncountable = [
        'equipment', 'information', 'rice', 'money', 'species', 'series',
        'fish', 'sheep', 'deer', 'aircraft', 'moose', 'salmon', 'trout',
        'bison', 'swine', 'offspring', 'news', 'police', 'staff', 'traffic',
        'furniture', 'luggage', 'baggage', 'advice', 'knowledge', 'software',
        'hardware', 'data', 'media', 'feedback', 'progress', 'research'
    ];

    if (in_array($lower, $uncountable, true)) {
        return $original;
    }

    // 2. Irregular plurals (Plural => Singular)
    $irregulars = [
        'people'   => 'person',
        'men'      => 'man',
        'women'    => 'woman',
        'children' => 'child',
        'teeth'    => 'tooth',
        'feet'     => 'foot',
        'geese'    => 'goose',
        'mice'     => 'mouse',
        'lice'     => 'louse',
        'oxen'     => 'ox',
        'cacti'    => 'cactus',
        'fungi'    => 'fungus',
        'nuclei'   => 'nucleus',
        'radii'    => 'radius',
        'stimuli'  => 'stimulus',
        'alumni'   => 'alumnus',
        'syllabi'  => 'syllabus',
        'foci'     => 'focus',
        'crises'   => 'crisis',
        'analyses' => 'analysis',
        'bases'    => 'basis',
        'diagnoses'=> 'diagnosis',
        'theses'   => 'thesis',
        'oases'    => 'oasis',
        'phenomena'=> 'phenomenon',
        'criteria' => 'criterion',
        'indices'  => 'index',
        'matrices' => 'matrix',
        'vertices' => 'vertex',
        'appendices'=> 'appendix',
        'leaves'   => 'leaf',
        'lives'    => 'life',
        'knives'   => 'knife',
        'wives'    => 'wife',
        'wolves'   => 'wolf',
        'calves'   => 'calf',
        'halves'   => 'half',
        'loaves'   => 'loaf',
        'shelves'  => 'shelf',
        'thieves'  => 'thief',
        'elves'    => 'elf',
        'sheaves'  => 'sheaf',
        'quizzes'  => 'quiz',
        'buses'    => 'bus',
        'moves'    => 'move',
        'sexes'    => 'sex',
        'shoes'    => 'shoe',
        'foes'     => 'foe',
        'toes'     => 'toe',
        // clothes (outfits.lol): "-ies" words that are really "-ie" words,
        // and sleeves, which the "ves -> fe" rule turns into "sleefe"
        'hoodies'  => 'hoodie',
        'beanies'  => 'beanie',
        'onesies'  => 'onesie',
        'booties'  => 'bootie',
        'ties'     => 'tie',     // bowties, neckties, panties
        'sleeves'  => 'sleeve',
    ];

    // Check exact match or suffix match for compound words (e.g., "firemen" -> "fireman")
    foreach ($irregulars as $plural => $singular) {
        if (preg_match('/(' . preg_quote($plural, '/') . ')$/i', $original, $matches)) {
            $matched = $matches[1];
            $replacement = matchCase($matched, $singular);
            return preg_replace('/' . preg_quote($matched, '/') . '$/', $replacement, $original);
        }
    }

    // 3. Ordered Regex Rules (Most specific to most general)
    $rules = [
        '/(matr)ices$/i'                                                  => '\1ix',
        '/(vert|ind)ices$/i'                                              => '\1ex',
        '/^(ox)en/i'                                                      => '\1',
        '/(alias|status)es$/i'                                            => '\1',
        '/(octop|vir)i$/i'                                                => '\1us',
        '/(cris|ax|test)es$/i'                                            => '\1is',
        '/(shoe)s$/i'                                                     => '\1',
        '/(o)es$/i'                                                       => '\1',
        '/(bus)es$/i'                                                     => '\1',
        '/([m|l])ice$/i'                                                  => '\1ouse',
        '/(x|ch|ss|sh)es$/i'                                              => '\1',
        '/(m)ovies$/i'                                                    => '\1ovie',
        '/(s)eries$/i'                                                    => '\1eries',
        '/([^aeiouy]|qu)ies$/i'                                           => '\1y',
        '/([lr])ves$/i'                                                   => '\1f',
        '/(tive)s$/i'                                                     => '\1',
        '/(hive)s$/i'                                                     => '\1',
        '/([^f])ves$/i'                                                   => '\1fe',
        '/(^analy)ses$/i'                                                 => '\1sis',
        '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i'=> '\1\2sis',
        '/([ti])a$/i'                                                     => '\1um',
        '/(n)ews$/i'                                                      => '\1ews',
        '/(ss)$/i'                                                        => '\1', // Prevent "glass" -> "glas"
        '/(us)$/i'                                                        => '\1', // Prevent "status" -> "statu"
        '/s$/i'                                                           => '',
    ];

    foreach ($rules as $pattern => $replacement) {
        if (preg_match($pattern, $original)) {
            return preg_replace($pattern, $replacement, $original);
        }
    }

    return $original;
}

/**
 * Helper to preserve capitalization (e.g., "People" -> "Person", "PEOPLE" -> "PERSON").
 */
function matchCase(string $reference, string $target): string
{
    if ($reference === strtoupper($reference)) {
        return strtoupper($target);
    }
    if ($reference[0] === strtoupper($reference[0])) {
        return ucfirst($target);
    }
    return strtolower($target);
}



/*
 *  ==== CLOTHES: plural category names, said the way CLIP likes ==========
 *  outfits.lol names categories plural, like aisles in a store ("Skirts",
 *  "Boots"). CLIP learned from captions like "a photo of a skirt", and on
 *  "a photo of skirts" it called a black quilted skirt Leggings (sample 11,
 *  2026-11-11). So shared.php's CLIP part asks SayPluralAsOneThing() first.
 *  New closet word acting weird? Fix it in this file, never in shared.php.
 */



/*
 *  GetWordsThatComeInPairs
 *  Nobody says "a legging". These stay plural and get "a pair of" in front.
 */
function GetWordsThatComeInPairs() {
	return array(
		'leggings', 'pants', 'jeans', 'shorts', 'tights', 'joggers', 'sweatpants',
		'overalls', 'panties', 'boots', 'booties', 'shoes', 'sneakers', 'sandals',
		'heels', 'flats', 'loafers', 'slippers', 'socks', 'stockings', 'gloves',
		'mittens', 'earrings', 'sunglasses', 'glasses',
	);
}



/*
 *  SayPluralAsOneThing
 *  "Skirts" -> "a skirt", "Leggings" -> "a pair of leggings",
 *  "Accessories" -> "an accessory". Words that aren't plural ("cosplay",
 *  "fancy") come back as they are, with no "a" stuck on.
 *  Everything comes back lowercase, for gluing into a caption.
 */
function SayPluralAsOneThing($plural_thing) {
	$plural_thing = strtolower(trim($plural_thing));

	// the LAST word is the noun: "long sleeve shirts", "combat boots"
	$every_word = explode(' ', $plural_thing);
	$last_word = end($every_word);

	if (in_array($last_word, GetWordsThatComeInPairs())) {
		return "a pair of $plural_thing";
	}

	$one_thing = singularize($plural_thing);

	// singularize() didn't change it, so it wasn't plural to begin with
	if ($one_thing === $plural_thing) {
		return $plural_thing;
	}

	// "a" or "an", good enough for clothes (sorry, "an hour-glass dress")
	$article = 'a';
	if (preg_match('/^[aeiou]/', $one_thing)) {
		$article = 'an';
	}
	// vowels that SOUND like "w" or "y": a onesie, a uniform, a used jacket
	if (preg_match('/^(one|uni|use|eu)/', $one_thing)) {
		$article = 'a';
	}

	return "$article $one_thing";
}
