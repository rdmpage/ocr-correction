<?php

class NameFinder
{
	function LookupNames($text) {	
		$complete = false;
		$attempts = 0;
		
		$url = "http://gnrd.globalnames.org/name_finder.json?text=" . urlencode($text);
		
		while ($complete == false) {		
			$curl = curl_init();
		
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
			
			$res = curl_exec($curl);
			curl_close($curl);
			
			$val = json_decode($res, true);
			$attempts++;
			
			if ($val['status'] != 303 || $attempts > 5) {			
				$complete = true;
			}
			else {
				sleep(2);
				$url = $val['token_url'];
			}
		}
				
		return $res;
	}
}

?>

