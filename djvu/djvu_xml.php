<?php

//--------------------------------------------------------------------------------------------------
function clean_xml($xml)
{
	$xml = str_replace("&#31;", "", $xml);
	$xml = str_replace("&#11;", "", $xml);
	
	return $xml;
}

//--------------------------------------------------------------------------------------------------
// From http://stackoverflow.com/questions/2312075/get-xpath-of-xml-node-within-recursive-function
function whereami($node)
{
    if ($node instanceof SimpleXMLElement)
    {
        $node = dom_import_simplexml($node);
    }
    elseif (!$node instanceof DOMNode)
    {
        die('Not a node?');
    }

    $q     = new DOMXPath($node->ownerDocument);
    $xpath = '';

    do
    {
        $position = 1 + $q->query('preceding-sibling::*[name()="' . $node->nodeName . '"]', $node)->length;
        $xpath    = '/' . $node->nodeName . '[' . $position . ']' . $xpath;
        $node     = $node->parentNode;
    }
    while (!$node instanceof DOMDocument);

    return $xpath;
}

//--------------------------------------------------------------------------------------------------
// Page size and DPI of DjVu page
function djvu_page_size(&$xpath)
{
	$page = new stdclass;

 	$nodeCollection = $xpath->query('//OBJECT/@height');
	foreach($nodeCollection as $node)
	{
		$page->height = $node->firstChild->nodeValue;
	}

 	$nodeCollection = $xpath->query('//OBJECT/@width');
	foreach($nodeCollection as $node)
	{
		$page->width = $node->firstChild->nodeValue;
	}
	
 	$nodeCollection = $xpath->query('//PARAM[@name="DPI"]/@value');
	foreach($nodeCollection as $node)
	{
		$page->dpi = $node->firstChild->nodeValue;
	}

	return $page;
}



//--------------------------------------------------------------------------------------------------
// bbox of DjVu XML document
function djvu_page_bbox(&$xpath)
{
	$bbox = array(0,0,0,0);

 	$nodeCollection = $xpath->query('//OBJECT/@height');
	foreach($nodeCollection as $node)
	{
		$bbox[1] = $node->firstChild->nodeValue;
	}

 	$nodeCollection = $xpath->query('//OBJECT/@width');
	foreach($nodeCollection as $node)
	{
		$bbox[2] = $node->firstChild->nodeValue;
	}

	return $bbox;
}


//--------------------------------------------------------------------------------------------------
function djvu_line_coordinates(&$xpath, $node, &$line_text)
{
	$line_bbox = array(100000,0,0,100000);
	$line_text = '';
	
	$words = $xpath->query ('WORD', $node);
	foreach($words as $word)
	{
		// coordinates
		if ($word->hasAttributes()) 
		{ 
			$attributes2 = array();
			$attrs = $word->attributes; 
			
			foreach ($attrs as $i => $attr)
			{
				$attributes2[$attr->name] = $attr->value; 
			}
		}
		
		$line_text .= $word->firstChild->nodeValue . ' ';
		$coords = explode(",", $attributes2['coords']);
		
		$line_bbox = merge_coordinates($line_bbox, $coords);
		$word_count++;
	}

	return $line_bbox;
}

//--------------------------------------------------------------------------------------------------
function djvu_words(&$xpath, $node)
{
	$x = 0;
	$y = 0;
	
	$word_list = array();
	
	$words = $xpath->query ('WORD', $node);
	foreach($words as $word)
	{
		// coordinates
		if ($word->hasAttributes()) 
		{ 
			$attributes2 = array();
			$attrs = $word->attributes; 
			
			foreach ($attrs as $i => $attr)
			{
				$attributes2[$attr->name] = $attr->value; 
			}
		}
		
		$coords = explode(",", $attributes2['coords']);
		
		$w = new stdclass;
		$w->text = $word->firstChild->nodeValue;
		$w->bbox = $coords;
		
		$word_list[] = $w;
	}

	return $word_list;
}



//--------------------------------------------------------------------------------------------------
// Coordinates of a WORD element in a DjVu XML document
function djvu_word_coordinates(&$xpath, $path)
{
	$path .= '/@coords';
 	$nodeCollection = $xpath->query($path);

	foreach($nodeCollection as $node)
	{
		$coords = explode(",", $node->firstChild->nodeValue);
	}
	return $coords;
}

//--------------------------------------------------------------------------------------------------
function merge_coordinates($c1, $c2)
{
	$coords=array();
	$coords[0] = min($c1[0], $c2[0]); // min-x
	$coords[1] = max($c1[1], $c2[1]); // max-y
	$coords[2] = max($c1[2], $c2[2]); // max-x
	$coords[3] = min($c1[3], $c2[3]); // min-y
	return $coords;
}

//--------------------------------------------------------------------------------------------------
function clean_text($text)
{
	$text = preg_replace('/</', '&lt;', $text);
	$text = preg_replace('/([^\d])- $/Uu', '$1&shy;', $text);
	$text = preg_replace('/([\d])- $/Uu', '$1-', $text);
	return $text;
}

//--------------------------------------------------------------------------------------------------
function clean_paragraph_text($p)
{
	$text = array();
	foreach ($p->lines as $line)
	{
		$t = $line->text;
		$text[] = clean_text($t);
	}
	return $text;
}