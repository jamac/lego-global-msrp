<?php

/*
Notes:
- Inital EU VAT rates collected from https://vatdesk.eu/
- I'm no expert on VAT, some of the rates could be wildly inaccurate.
- Outputs CSV. Pipe to file if needed (ie php lego-global-msrp.php > prices.csv)
- Only considers prices found on Lego.com.
*/

/* ************************************************************************** */
// Process Configuration

	$iniFile = "config.ini";
	$iniFileDefault = "default.config.ini";

	if (!file_exists($iniFile) || !is_readable($iniFile)) {
		fwrite(STDERR, 'Not Configured.' . PHP_EOL);
		fwrite(STDERR, 'Rename ' . $iniFileDefault . ' to ' . $iniFile . ' and alter item IDs, base currency, etc. as needed.' . PHP_EOL);
		exit(1);
	}

	$config = parse_ini_file('./' . $iniFile, TRUE);

	$baseCurrencyID = $config['config']['basecurrency'];
	$sleepTime = $config['config']['sleeptime'];
	$currencyURL = $config['urls']['currency'];
	$urlPattern = $config['urls']['product'];
	$itemids = array_filter($config['sets']['itemids']);
	$locales = $config['locales'];

/* ************************************************************************** */
// Build currency conversion data

	$currencyString = file_get_contents($currencyURL);
	$currencyXML = new SimpleXMLElement($currencyString);
	$currencyPaths = $currencyXML->xpath('//*[@currency]');

	$baseCurrencyValue = 1;
	if ($baseCurrencyID != 'EUR') {
		$baseCurrencyPath = $currencyXML->xpath('//*[@currency="' . $baseCurrencyID . '"]');
		$baseCurrencyValue = floatval(strval($baseCurrencyPath[0]['rate']));
	}

	$currencies = array();

	foreach ($currencyPaths as $currency) {
		$currencyID = strval($currency['currency']);
		$currencyRate = floatval(strval($currency['rate'])) / $baseCurrencyValue;
		$currencies[$currencyID] = $currencyRate;
	}

	// Add in the XML's base rate
	$currencies['EUR'] = 1 / $baseCurrencyValue;

/* ************************************************************************** */
// Collect details from LEGO.com

	// The bits we want to collect
	$properties = array(
		'og:locale',
		'product:availability',
		'product:price:amount',
		'product:price:currency',
		'computed:locale',
		'computed:locale:vat',
		'computed:price',
		'computed:price:novat',
		'computed:currency',
		'computed:currency:rate',
		'og:title',
		'og:url',
	);

	$headersPrinted = false;

	foreach ($itemids as $itemID) {

		foreach ($locales as $localeID => $locale) {

			$details = array(
				'computed:itemid' => $itemID,
				'computed:locale' => $locale['name'],
				'computed:locale:vat' => $locale['vat'],
			);

			$locale['vat'] += 1;

			// Load URL and apply UTF-8 hack for DomDocument
			$html = file_get_contents(sprintf($urlPattern, $localeID, $itemID));
			$html = str_replace('<head>', '<head><meta http-equiv="content-type" content="text/html; charset=utf-8">', $html);

			$doc = new DomDocument();
			$doc->validateOnParse = false;
			@$doc->loadHTML($html);

			$elements = $doc->getElementsByTagName('meta');

			foreach ($elements as $element) {

				$property = $element->getAttribute('property');
				$content = $element->getAttribute('content');

				if (in_array($property, $properties)) {
					$details[$property] = trim($content);
				}

			}

			$details['computed:price'] = 'N/A';
			$details['computed:price:novat'] = 'N/A';
			$details['computed:currency'] = $baseCurrencyID;
			$details['computed:currency:rate'] = 'N/A';
			if ($details['product:price:amount'] && $currencies[$details['product:price:currency']]) {
				$details['computed:price'] = number_format($details['product:price:amount'] / $currencies[$details['product:price:currency']], 2);
				$details['computed:price:novat'] = number_format($details['computed:price'] / $locale['vat'], 2);
				$details['computed:currency:rate'] = number_format(1 / $currencies[$details['product:price:currency']], 4);
			}
			// Strip out the shop at lego bits
			if ($details['og:title']) {
				$details['og:title'] = trim(reset(explode('|', $details['og:title'])));
			}

			if (!$headersPrinted) {
				$headersPrinted = true;
				print('"' . implode('","', array_keys($details)) . '"' . PHP_EOL);
			}

			print('"' . implode('","', array_values($details)) . '"' . PHP_EOL);

			sleep($sleepTime);

		}

	}
