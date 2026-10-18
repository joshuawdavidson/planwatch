<?php
/**
 * Find_RSS_Links
 * A class to fetch RSS (1.0 and 2.0) links (embedded in the page's HTML)
 *
 * @package Find_RSS_Links
 * @author MT-Soft
 * @copyright Copyright (c) 2007
 * @version 1.0
 * @access public
 */
class Find_RSS_Links
{
	/**
	 * string $url
	 * @access private
	 *
	 */
	var $url;


	/**
	 * Constructor
	 * Find_RSS_Links::Find_RSS_Links()
	 *
	 * @param string $url
	 * @return void
	 */
	function Find_RSS_Links($url='')
	{
		$this->url = $url;
	}

	/**
	 * Find_RSS_Links::_robots_allowed()
	 * Find whether robots are allowed to fetch content from the site (using robots.txt)
	 * Original PHP code by Chirp Internet: http://www.chirp.com.au
	 *
	 * @param string $url
	 * @param string $useragent
	 * @return boolean
	 */
	function _robots_allowed($url='', $useragent = '')
	{
		if($url=='')
			$url = $this->url;
		if($url=='')
			return false;

		// parse url to retrieve host and path
		$parsed = parse_url($url);
		$agents = array(preg_quote('*'));
		if ($useragent) $agents[] = preg_quote($useragent);
		$agents = implode('|', $agents);

		// location of robots.txt file
		/*$robotstxt = @file("http://{$parsed['host']}/robots.txt");
		if (!$robotstxt) return true;
		$rules = array();
		$ruleapplies = false;
		foreach($robotstxt as $line)
		{
			// skip blank lines
			if (!$line = trim($line))
				continue;
			// following rules only apply if User-agent matches $useragent or '*'
			if (preg_match('/User-agent: (.*)/i', $line, $match))
			{
				$ruleapplies = preg_match("/($agents)/i", $match[1]);
			}
			if ($ruleapplies && preg_match('/Disallow:(.*)/i', $line, $regs))
			{
				// an empty rule implies full access - no further tests required
				if (!$regs[1]) return true;
				// add rules that apply to array for testing
				$rules[] = preg_quote(trim($regs[1]), '/');
			}
		}
		foreach($rules as $rule)
		{
			// check if page is disallowed to us
			if (preg_match("/^$rule/", $parsed['path'])) return false;
		}
		// page is not disallowed
		*/
		return true;
	}

	/**
	 * Find_RSS_Links::_get_page_contents()
	 * Fetch Contents of Page (from URL). Requires curl
	 *
	 * @param string $url
	 * @return string
	 */
	function _get_page_contents($url='')
	{
		if($url=='')
			$url = $this->url;

		$ch = curl_init($url);
//		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: close'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		$html = curl_exec($ch);
		curl_close($ch);

		return $html;
	}

	/**
	 * Find_RSS_Links::getLinks()
	 * Get an array or RSS links.
	 *
	 * Based on RSS auto-discovery by Keith Devens.com http://keithdevens.com/weblog/archive/2002/Jun/03/RSSAuto-DiscoveryPHP
	 * @param string $url
	 * @return string Array
	 */
	function getLinks($url='')
	{
		//Check whether
		if($url=='')
		{
			$url = $this->url;
		}
		else
		{
			$this->url = $url;
		}

		// Set user agent, for robots.txt checking...
		ini_set('user_agent', 'MT-Soft (http://www.mt-soft.com.ar)');
		$url_list = array();
		// Check if required data was provided. If not, return empty list.
		if (!trim($url))
		{
			return $url_list;
		}


		// Check if we're allowed to spider content. Being polite!!!
		if (!$this->_robots_allowed($location, "MT-Soft"))
		{
			print "not allowed";
			return $url_list;
		}
		else
		{
			$html = $this->_get_page_contents($url);
			$location = $url;

			// Search through the HTML, save all <link> tags and store each link's attributes in an associative array
			preg_match_all('/<link\s+(.*?)\s*\/?>/si', $html, $matches);
			$links = $matches[1];
			preg_match_all('/<a.* href=[\'"](.*?)[\'"][^\>]*>/i', $html, $anchormatches);
			$anchorlinks=$anchormatches[1];

			foreach($anchorlinks as $anchor)
			{
				if (strstr($anchor,'feed') || strstr($anchor,'xml') || strstr($anchor,'rss') || strstr($anchor,'atom'))
					$url_list[]=$anchor;
			}

			$final_links = array();
			$link_count = count($links);
			for($n = 0; $n < $link_count; $n++)
			{
				$attributes = preg_split('/\s+/s', $links[$n]);
				foreach($attributes as $attribute)
				{
					$att = preg_split('/\s*=\s*/s', $attribute, 2);
					if (isset($att[1]))
					{
						$att[1] = preg_replace('/([\'"]?)(.*)\1/', '$2', $att[1]);
						$final_link[strtolower($att[0])] = $att[1];
					}
				}
				$final_links[$n] = $final_link;
			}

			// now figure out which ones point to the RSS file
			for($n = 0; $n < $link_count; $n++)
			{
				$href ='';
				if (strtolower($final_links[$n]['rel']) == 'alternate' or strtolower($final_links[$n]['rel']) == 'outline')
				{
					if (strtolower($final_links[$n]['type']) == 'application/rss+xml')
					{
						$href = $final_links[$n]['href'];
					}
					if (!$href and strtolower($final_links[$n]['type']) == 'text/xml')
					{
						// kludge to make the first version of this still work
						$href = $final_links[$n]['href'];
					}
					if (!$href and strtolower($final_links[$n]['type']) == 'application/atom+xml')
					{
						// Find ATOM feeds
						$href = $final_links[$n]['href'];
					}
					if (!$href and in_array(strtolower($final_links[$n]['type']),  array('text/x-opml', 'application/xml', 'text/xml')) and
							preg_match("/\.opml$/", $final_links[$n]['href']))
					{
						// Find OPML outlines
						$href = $final_links[$n]['href'];
					}
					if ($href)
					{
						if (strstr($href, "http://") !== false)
						{ // if it's absolute
							$full_url = $href;
						}
						else
						{ // otherwise, 'absolutize' it
							$url_parts = parse_url($location);
							// only made it work for http:// links. Any problem with this?
							$full_url = "http://$url_parts[host]";
							if (isset($url_parts['port']))
							{
								$full_url .= ":$url_parts[port]";
							}
							if ($href{0} != '/')
							{ // it's a relative link on the domain
								$full_url .= dirname($url_parts['path']);
								if (substr($full_url, -1) != '/')
								{
									// if the last character isn't a '/', add it
									$full_url .= '/';
								}
							}
							$full_url .= $href;
						}

						// Only add the feed URL if not already on the list
						if(!in_array($full_url, $url_list))
						{
							$url_list[] = $full_url;
						}
					}
				}
			}
		}

		return $url_list;
	}
}


?>