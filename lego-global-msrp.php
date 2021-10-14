<?php

/*
Notes:
- Inital EU VAT rates collected from https://vatdesk.eu/
- I'm no expert on VAT, some of the rate could be wildly inaccurate.
- Outputs CSV. Pipe to file if needed (ie php lego-global-msrp.php > prices.csv)
- Only considers prices found on Lego.com.
*/

/* ************************************************************************** */
// Build currency conversion data

	$baseCurrencyID = 'USD';
	$currencyURL = 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';

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

	$sleepTime = 10;

	$headersPrinted = false;
	$urlPattern = 'https://www.lego.com/%s/product/%s';

	$itemIDs = array(
		21324, // Sesame Street (Ideas)
		60197, // Passenger Train (City)
		75318, // The Child (Star Wars)
		75978, // Diagon Alley (Harry Potter)
		76161, // 1989 Batwing (DC)
		71395, // Super Mario 64 Question Mark Block
	);

	// Cherry picking English were possible
	$countryIDs = array(

		// North America
		'en-ca' => array(
			'name' => 'Canada',
			'vat' => 0.0,
		),
		'en-mx' => array(
			'name' => 'Mexico',
			'vat' => 0.0,
		),
		'en-us' => array(
			'name' => 'United States',
			'vat' => 0.0,
		),

		// South America

		// Europe
		'en-be' => array(
			'name' => 'Belgium',
			'vat' => 0.21,
		),
		'en-cz' => array(
			'name' => 'Czech Republic',
			'vat' => 0.21,
		),
		'en-dk' => array(
			'name' => 'Denmark',
			'vat' => 0.25,
		),
		'en-de' => array(
			'name' => 'Germany',
			'vat' => 0.19,
		),
		'en-ee' => array(
			'name' => 'Estonia',
			'vat' => 0.20,
		),
		'en-es' => array(
			'name' => 'Spain',
			'vat' => 0.21,
		),
		'en-fi' => array(
			'name' => 'Finland',
			'vat' => 0.24,
		),
		'en-fr' => array(
			'name' => 'France',
			'vat' => 0.20,
		),
		'en-gr' => array(
			'name' => 'Greece',
			'vat' => 0.24,
		),
		'en-hu' => array(
			'name' => 'Hungary',
			'vat' => 0.27,
		),
		'en-ie' => array(
			'name' => 'Ireland',
			'vat' => 0.23,
		),
		'en-it' => array(
			'name' => 'Italy',
			'vat' => 0.22,
		),
		'en-lv' => array(
			'name' => 'Latvia',
			'vat' => 0.21,
		),
		'en-lt' => array(
			'name' => 'Lithuania',
			'vat' => 0.21,
		),
		'en-lu' => array(
			'name' => 'Luxemburg',
			'vat' => 0.17,
		),
		'en-nl' => array(
			'name' => 'Netherlands',
			'vat' => 0.21,
		),
		'en-no' => array(
			'name' => 'Norway',
			'vat' => 0.25,
		),
		'en-at' => array(
			'name' => 'Austria',
			'vat' => 0.20,
		),
		'en-pl' => array(
			'name' => 'Poland',
			'vat' => 0.23,
		),
		'en-pt' => array(
			'name' => 'Portugal',
			'vat' => 0.23,
		),
		'en-ch' => array(
			'name' => 'Switzerland',
			'vat' => 0.077,
		),
		'en-si' => array(
			'name' => 'Slovenia',
			'vat' => 0.22,
		),
		'en-sk' => array(
			'name' => 'Slovakia',
			'vat' => 0.20,
		),
		'en-se' => array(
			'name' => 'Sweden',
			'vat' => 0.25,
		),
		'en-gb' => array(
			'name' => 'United Kingdom',
			'vat' => 0.20,
		),

		// Asia Pacific
		'en-au' => array(
			'name' => 'Australia',
			'vat' => 0.0,
		),
		'en-nz' => array(
			'name' => 'New Zealand',
			'vat' => 0.0,
		),
		'ko-kr' => array(
			'name' => 'Korea',
			'vat' => 0.0,
		),

		// Middle East & Africa

		/*
		// Disabled - Do not appear to list price
		'pt-br' => array(
			'name' => 'Brasil',
			'vat' => 0.0,
		),
		'zh-cn' => array(
			'name' => 'China',
			'vat' => 0.0,
		),
		'en-in' => array(
			'name' => 'India',
			'vat' => 0.0,
		),
		'ja-jp' => array(
			'name' => 'Japan',
			'vat' => 0.0,
		),
		'es-ar' => array(
			'name' => 'Latin America',
			'vat' => 0.0,
		),
		'en-my' => array(
			'name' => 'Malaysia',
			'vat' => 0.0,
		),
		'ru-ru' => array(
			'name' => 'Russia',
			'vat' => 0.0,
		),
		'en-sg' => array(
			'name' => 'Singapore',
			'vat' => 0.0,
		),
		'en-za' => array(
			'name' => 'South Africa',
			'vat' => 0.0,
		),
		'tr-tr' => array(
			'name' => 'Turkey',
			'vat' => 0.0,
		),
		'en-ae' => array(
			'name' => 'United Arab Emirates',
			'vat' => 0.0,
		),
		*/

	);

	// The bits we want to collect
	$properties = array(
		'og:title',
		'og:description',
		'og:url',
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
	);

	foreach ($itemIDs as $itemID) {

		foreach ($countryIDs as $countryID => $country) {

			$details = array(
				'computed:itemid' => $itemID,
				'computed:locale' => $country['name'],
				'computed:locale:vat' => $country['vat'],
			);

			$country['vat'] += 1;

			// Load URL and apply UTF-8 hack for DomDocument
			$html = file_get_contents(sprintf($urlPattern, $countryID, $itemID));
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
				$details['computed:price:novat'] = number_format($details['computed:price'] / $country['vat'], 2);
				$details['computed:currency:rate'] = number_format(1 / $currencies[$details['product:price:currency']], 4);
			}

			if (!$headersPrinted) {
				$headersPrinted = true;
				print('"' . implode('","', array_keys($details)) . '"' . PHP_EOL);
			}

			print('"' . implode('","', array_values($details)) . '"' . PHP_EOL);

			sleep($sleepTime);

		}

	}
