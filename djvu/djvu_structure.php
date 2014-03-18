<?php

// DjVu to HTML
require_once (dirname(__FILE__) . '/djvu_xml.php');

//--------------------------------------------------------------------------------------------------
function mean($a)
{
	$average = 0;
	$n = count($a);
	$sum = 0;
	foreach ($a as $x)
	{
		$sum += $x;
	}
	$average = $sum/$n;
	return $average;
}
	
//--------------------------------------------------------------------------------------------------
function structure($xml_filename)
{
	$page = null;
	
	$pagenum = $xml_filename;
	$pagenum = str_replace(".xml", "", $pagenum);
	
	// Get DjVu page number
	preg_match('/(?<id>\d+).xml$/', $xml_filename, $m);
	$id = $m['id'];
	
	// Get XML
	$xml = file_get_contents($xml_filename);
	
	// Remove any spurious things which break XML parsers
	$xml = clean_xml($xml);
		
	//echo $xml;
		
	$dom= new DOMDocument;
	$dom->loadXML($xml);
	$xpath = new DOMXPath($dom);
	
	// Create page object from XML file to hold things such as bounding boxes
	$bbox = djvu_page_bbox($xpath);
	
	$page = new stdclass;
	$page->regions = array();
	$page->dpi = 0;
	
	// Get DPI
	$nodes = $xpath->query ('//PARAM[@name="DPI"]/@value');
	foreach($nodes as $node)
	{
		$page->dpi = $node->firstChild->nodeValue;
	}
	
	// Get physical page bounding box
	$page->bbox = array(0,0,0,0);
	$nodes = $xpath->query ('//OBJECT/@width');
	foreach($nodes as $node)
	{
		$page->bbox[2] = $node->firstChild->nodeValue;
	}
	$nodes = $xpath->query ('//OBJECT/@height');
	foreach($nodes as $node)
	{
		$page->bbox[1] = $node->firstChild->nodeValue;
	}
	$page->text_bbox = array($page->bbox[2],0,0,$page->bbox[1]);
	
		//------------------------------------------------------------------------------------------
	// Regions, paragraphs, and lines on page
	$regions = $xpath->query ('//REGION');		
	foreach($regions as $region)
	{
		$region_object = new stdclass;
		
		// Initialise region bounding box
		$region_object->bbox = array(10000,0,0,10000);
		
		// Paragraphs
		$region_object->paragraphs = array();
				
		$paragraphs = $xpath->query ('PARAGRAPH', $region);
		foreach ($paragraphs as $paragraph)
		{
			$paragraph_object = new stdclass;
			
			// Initialise paragraph bounding box
			$paragraph_object->bbox = array(10000,0,0,10000);
			
			// 
			$paragraph_object->line_heights = array();
			
			// Lines
			$paragraph_object->lines = array();
			$lines = $xpath->query ('LINE', $paragraph);
			foreach ($lines as $line)
			{
				$line_object = new stdclass;
				$line_object->text = '';
				
				// Add line bbox to paragraph bbox
				$line_object->bbox = djvu_line_coordinates($xpath, $line, $line_object->text);
				$paragraph_object->bbox = merge_coordinates($paragraph_object->bbox, $line_object->bbox);
				
				// Extract words
				$line_object->words = djvu_words($xpath, $line);
				
				// Font info
				$line_object->baseline = $page->bbox[1];
				$line_object->capheight = 0;
				$line_object->descender = $page->bbox[1];
				$line_object->ascender = 0;
				
				foreach ($line_object->words as $word)
				{
					//echo $word->text . " ";
				
					// Get font dimensions for this line
					
					if (preg_match('/[tdfhklb]/', $word->text))
					{
						$line_object->ascender = max($line_object->ascender, $word->bbox[3]);
						$line_object->baseline = min($line_object->baseline, $word->bbox[1]);
					}

					if (preg_match('/[qypgj]/', $word->text))
					{
						$line_object->descender = min($line_object->descender, $word->bbox[1]);
					}

					if (preg_match('/[A-Z0-9]/', $word->text))
					{
						$line_object->capheight = max($line_object->capheight, $word->bbox[3]);
						$line_object->baseline = min($line_object->baseline, $word->bbox[1]);
					}
					
				}	
				
				$line_object->fontmetrics = new stdclass;
				
				if ($line_object->baseline != $page->bbox[1])
				{
					$line_object->fontmetrics->baseline = $line_object->baseline;
				
					if ($line_object->ascender != 0)
					{
						$line_object->fontmetrics->ascender = $line_object->baseline - $line_object->ascender;
					}
					
					if ($line_object->capheight != 0)
					{
						$line_object->fontmetrics->capheight = $line_object->baseline - $line_object->capheight;
					}
					
					if ($line_object->descender != $page->bbox[1])
					{
						$line_object->fontmetrics->descender = $line_object->descender - $line_object->baseline;
					}
				}
				
				/*
				echo " ascender: " . $line_object->ascender . "\n";
				echo "capheight: " . $line_object->capheight . "\n";
				echo " baseline: " . $line_object->baseline . "\n";
				echo "descender: " . $line_object->descender . "\n";
				echo "---\n";
				echo " ascender: " . ($line_object->baseline - $line_object->ascender) . "\n";
				echo "capheight: " . ($line_object->baseline - $line_object->capheight) . "\n";
				echo " baseline: 0\n";
				echo "descender: " . ($line_object->descender - $line_object->baseline) . "\n";
				echo "---\n";
				*/
				
				
				$paragraph_object->baselines[] = $line_object->baseline;
		
				$paragraph_object->lines[] = $line_object;
			}
			
			// Add paragraph bbox to region bbox
			$region_object->bbox = merge_coordinates($region_object->bbox, $paragraph_object->bbox);		
						
			$region_object->paragraphs[] = $paragraph_object;
		}
				
		$page->regions[] = $region_object;
	}

	return $page;
}

//--------------------------------------------------------------------------------------------------
function extract_font_sizes($page)
{
	// Compute font sizes
	foreach ($page->regions as $region)
	{
		foreach ($region->paragraphs as $paragraph)
		{
			//echo count($paragraph->lines) . "\n";
			//print_r($paragraph->bbox);
			
			$fontmetrics = new stdclass;
			
			$count = 0;
			$last_baseline = 0;
			foreach ($paragraph->lines as $line)
			{
				//echo "line->fontmetrics\n";
				//print_r($line->fontmetrics);
				
				//echo $line->text . "\n";
				
				if ($count > 0 && isset($line->fontmetrics->baseline))
				{
					$fontmetrics->linespacing[] = $line->fontmetrics->baseline - $last_baseline;
				}
				$count++;
				$last_baseline = $line->fontmetrics->baseline;
				
				if (isset($line->fontmetrics->ascender)) { $fontmetrics->ascender[] = $line->fontmetrics->ascender; }
				if (isset($line->fontmetrics->capheight)) { $fontmetrics->capheight[] = $line->fontmetrics->capheight; }
				if (isset($line->fontmetrics->descender)) { $fontmetrics->descender[] = $line->fontmetrics->descender; }
			}
			//print_r($fontmetrics);
			
			$paragraph->fontmetrics = new stdclass;
			
			if (isset($fontmetrics->linespacing))
			{
				$paragraph->fontmetrics->linespacing = mean($fontmetrics->linespacing);
			}
			else
			{
				$paragraph->fontmetrics->linespacing = -1;
			}
			if (isset($fontmetrics->ascender))
			{
				$paragraph->fontmetrics->ascender = mean($fontmetrics->ascender);
			}
			if (isset($fontmetrics->capheight))
			{
				$paragraph->fontmetrics->capheight = mean($fontmetrics->capheight);
			}
			if (isset($fontmetrics->descender))
			{
				$paragraph->fontmetrics->descender = mean($fontmetrics->descender);
			}
			
			//print_r($paragraph->fontmetrics);
			
		}
	}
}
		
//--------------------------------------------------------------------------------------------------
// Get lines of text
function lines($page, &$obj)
{
	$scale = $obj->image->width/$page->bbox[2];
	
	$line_counter = 0;

	foreach ($page->regions as $region)
	{
		
		foreach ($region->paragraphs as $paragraph)
		{			
			// font height
			$fontsize = 0;
			
			// Compute font height based on capheight of font
			// e.g for Times New Roman we divide by 0.662
			if (isset($paragraph->fontmetrics->capheight))
			{
				$fontsize = $paragraph->fontmetrics->capheight/0.662;
			}
			
			$linespacing = $paragraph->fontmetrics->linespacing;
			if ($linespacing != -1)
			{
				$linespacing = round($linespacing/$page->dpi * 72);
			}
				
			$fontsize *= $scale;
												
			// text
			foreach ($paragraph->lines as $line)
			{
				$ocr_line = new stdclass;
				$ocr_line->id = "line" . $line_counter++;
				$ocr_line->fontsize = $fontsize;
				$ocr_line->bbox = $line->bbox;
				$ocr_line->text = preg_replace('/\s+$/', '', $line->text);
				
				$obj->lines[] = $ocr_line;
			}
		}		
	}

}


?>