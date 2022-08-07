<?php

/*
********************************************************************************
	Notes:
	- Outputs CSV. Pipe to file if needed (ie php all-prices.php > prices.csv)
	- Only considers prices found on Lego.com.
********************************************************************************
*/

	// Process Configuration
	require("./config.php");

	$pageSleep = $config['config']['pagesleep'];
	$localeSleep = $config['config']['localesleep'];
	$urlPattern = $config['urls']['allsets'];
	$locales = $config['locales'];

	$startAtPage = 1;

	$headers = array(
		"locale",
		"productCode",
		"name",
		"slug",
		"primaryImage",
		"sku",
		"availabilityStatus",
		"canAddToBag",
		"isNew",
		"maxOrderQuantity",
		"onSale",
		"priceCurrencyCode",
		"priceFormattedValue",
		"priceFormattedAmount",
		"listPriceFormattedAmount",
	);

	fputcsv(STDOUT, $headers);

	// Loop through each locale
	ksort($locales);
	foreach ($locales as $localeID => $locale) {

		// We'll find the real number of pages when we process the first page for this locale
		$totalPages = 1;
		for ($currentPage = 1; $currentPage <= $totalPages; $currentPage++) {

			// Obtain raw HTML
			// Don't append ?page= if current page equals 1
			$url = sprintf($urlPattern, $localeID, ($currentPage == 1 ? "" : "?page=" . $currentPage));
			$response = file($url);
			$html = end($response);

			// Prune down to the JSON text
			preg_match("`<script id=\"__NEXT_DATA__\" type=\"application/json\">(.*?)</script>`", $html, $matches);
			if (!$matches[1]) print "No JSON in " . $url . PHP_EOL;

			// Prune down to the JSON object we want
			$decoded = json_decode($matches[1]);
			$json = $decoded->props->pageProps->__APOLLO_STATE__;
			$keys = array_keys((array)$json);

			// Locate the SingleVariantProduct IDs we want
			foreach ($keys as $key) {
				if (strpos($key, 'ProductSection') !== false) {
					if (!isset($json->$key->results)) continue;
					// Update total pages based on number of items available
					// and the number of results on the first page
					if ($currentPage == 1) {
						$totalPages = (int) ceil($json->$key->total / $json->$key->count);
					}
					$SingleVariantProductIDs = array_column((array)$json->$key->results, 'id');
					break;
				}
			}

			// Process all relevant Objects for each SingleVariantProduct
			$rowItems = array();
			foreach ($SingleVariantProductIDs as $SingleVariantProductID) {

				$SingleVariantProductObj = $json->$SingleVariantProductID;

				$ProductVariantObj = $json->{$SingleVariantProductObj->variant->id};
				$ProductVariantAttributeObj = $json->{$ProductVariantObj->attributes->id};
				$ProductVariantPriceObj = $json->{$ProductVariantObj->price->id};
				$ProductVariantListPriceObj = $json->{$ProductVariantObj->listPrice->id};

				// Output data of interest
				fputcsv(STDOUT, array(
					$localeID,
					$SingleVariantProductObj->productCode,
					$SingleVariantProductObj->name,
					$SingleVariantProductObj->slug,
					$SingleVariantProductObj->primaryImage,
					$ProductVariantObj->sku,
					$ProductVariantAttributeObj->availabilityStatus,
					$ProductVariantAttributeObj->canAddToBag,
					$ProductVariantAttributeObj->isNew,
					$ProductVariantAttributeObj->maxOrderQuantity,
					$ProductVariantAttributeObj->onSale,
					$ProductVariantPriceObj->currencyCode,
					$ProductVariantPriceObj->formattedValue,
					$ProductVariantPriceObj->formattedAmount,
					$ProductVariantListPriceObj->formattedAmount,
				));

			}

			// Pause after each page
			sleep($pageSleep);

		}

		// Pause after each locale
		sleep($localeSleep);

	}
