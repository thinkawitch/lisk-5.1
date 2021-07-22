<?php

// Search engines defenitions for comments
$GLOBALS['STAT_VISIT_SEARCH_ENGINES'] = array(
	'google'	=> array(
		'regexp'		=> '/^(?:www.|)google\.[\w]{2,3}\.?[\w]{0,3}$/',
		'keyword_param'	=> 'q'
	),
	'msn'		=> array(
		'regexp'		=> '/^search\.msn\.[\w]{2,3}\.?[\w]{0,3}$/',
		'keyword_param'	=> 'q'
	),
	'bing'		=> array(
		'regexp'		=> '/^(?:www.|)bing\.[\w]{2,3}\.?[\w]{0,3}$/',
		'keyword_param'	=> 'q'
	),
	'yahoo'		=> array(
		'regexp'		=> '/^search\.yahoo\.[\w]{2,3}\.?[\w]{0,3}$/',
		'keyword_param'	=> 'p'
	),
	'baidu'		=> array(
		'regexp'		=> '/^(?:www.|)baidu\.com$/',
		'keyword_param'	=> 'wd'
	)
);


if (!defined('DAY')) define ('DAY', 60 * 60 * 24);


$GLOBALS['LIST_STAT_TIMES'] = array(
	14 * DAY 	=> "Last 2 Weeks",
	30 * DAY 	=> "Last Month",
	92 * DAY 	=> "Last 3 Months",
	183 * DAY 	=> "Last 6 Months",
	9999 * DAY 	=> "All Time",
);


$GLOBALS['LIST_STAT_TOP'] = array(
	"Pages" => "Pages",
	"Referrers" => "Referrers",
	"Enter Pages" => "Enter Pages"
);



$GLOBALS['LIST_STAT_PERIODS'] = array(
	1 * DAY => "Last Day",
	2 * DAY => "Last 2 Days",
	7 * DAY => "Last Week",
	14 * DAY => "Last 2 Weeks",
	30 * DAY => "Last Month",
	92 * DAY => "Last 3 Months",
	183 * DAY => "Last 6 Months",
	9999 * DAY => "All Time Before",
);


$GLOBALS['LIST_STAT_VISIT_TREND_PERIODS'] = array(
	7 * DAY => "Last Week",
	14 * DAY => "Last 2 Weeks",
	30 * DAY => "Last Month",
	92 * DAY => "Last 3 Months",
	183 * DAY => "Last 6 Months"
);


// ================================================================================
// BOUNCE RATE STATISTICS DATA
// ================================================================================

$GLOBALS['LIST_STAT_BOUNCE'] = array(
	"1",
	"2",
	"3",
	"4",
	"5",
	"6",
	"7",
	"8",
	"9",
	"10 - 14",
	"15 - 19",
	"20 +",
);

$GLOBALS['DATA_STAT_BOUNCE'] = array(
	'fields' => array (
		'date' => array (
			'type' => 'date',
		),
		'period' => array (
			'type' => 'list',
			'object' => 'def_stat_periods'
		),
	)
);

// ================================================================================
// TECH STATISTICS DATA
// ================================================================================

$GLOBALS['LIST_STAT_TECH_BROWSERS_'] = array(
	"" => "Unknown Browser",
	"compatible" => "Netscape Navigator",
	"msie" => "Internet Explorer",
	"firefox" => "Firefox",
	"icab" => "iCab",
	"webtv" => "WebTV",
	"opera" => "Opera",
	"omniweb" => "OmniWeb",
	"safari" => "Safari",
	"konqueror" => "Konqueror",
);
foreach ($GLOBALS['LIST_STAT_TECH_BROWSERS_'] as $value) $GLOBALS['LIST_STAT_TECH_BROWSERS'][] = $value;

$GLOBALS['LIST_STAT_TECH_OS_'] = array(
	"" => "Unknown OS",
	"win" => "Windows",
	"mac" => "Mac",
	"x11" => "Unix",
	"linux" => "Linux",
);
foreach ($GLOBALS['LIST_STAT_TECH_OS_'] as $value) $GLOBALS['LIST_STAT_TECH_OS'][] = $value;

// ================================================================================
// TRENDS STATISTICS DATA
// ================================================================================

$GLOBALS['LIST_STAT_TRENDS_PERIODS'] = array(
	1 * DAY => "Last Day",
	7 * DAY => "Last Week",
	31 * DAY => "Last Month",
	365 * DAY => "Last Year",
);

$GLOBALS['DATA_STAT_TRENDS']= array(
	'fields' => array (
		'date' => array (
			'type' => LiskType::TYPE_DATE,
		),
		'period' => array (
			'type' => LiskType::TYPE_LIST,
			'object' => 'def_stat_periods'
		),
	)
);

// ================================================================================
// RANDOM STATISTICS DATA
// ================================================================================

$GLOBALS['LIST_USER_AGENTS'] = array(
// :: BROWSERS - Windows ::
	"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)",
	"Mozilla/4.0 (compatible; MSIE 5.5; Windows NT 5.0 )",
	"Mozilla/4.0 (compatible; MSIE 5.5; Windows 98; Win 9x 4.90)",
	"Mozilla/4.8 [en] (Windows NT 5.1; U)",
	"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0; en) Opera 8.0",
	"Opera/7.51 (Windows NT 5.1; U) [en]",
	"Opera/7.50 (Windows XP; U)",
	"Avant Browser/1.2.789rel1 (http://www.avantbrowser.com)",
	"Mozilla/5.0 (Windows; U; Win98; en-US; rv:1.4) Gecko Netscape/7.1 (ax)",
	"Mozilla/5.0 (Windows; U; Windows XP) Gecko MultiZilla/1.6.1.0a",
	"Opera/7.50 (Windows ME; U) [en]",
	"Mozilla/3.01Gold (Win95; I)",
	"Mozilla/2.02E (Win95; U)",
// :: BROWSERS -  Mac ::
	"Mozilla/5.0 (Macintosh; U; PPC Mac OS X; en) AppleWebKit/125.2 (KHTML, like Gecko) Safari/125.8",
	"Mozilla/5.0 (Macintosh; U; PPC Mac OS X; en) AppleWebKit/125.2 (KHTML, like Gecko) Safari/85.8",
	"Mozilla/4.0 (compatible; MSIE 5.15; Mac_PowerPC)",
	"Mozilla/5.0 (Macintosh; U; PPC Mac OS X Mach-O; en-US; rv:1.7a) Gecko/20040614 Firefox/0.9.0+",
	"Mozilla/5.0 (Macintosh; U; PPC Mac OS X; en-US) AppleWebKit/125.4 (KHTML, like Gecko, Safari) OmniWeb/v563.15",
// :: BROWSERS - linux/unix/beos ::
	"Mozilla/5.0 (X11; U; Linux; i686; en-US; rv:1.6) Gecko Debian/1.6-7",
	"Mozilla/5.0 (X11; U; Linux; i686; en-US; rv:1.6) Gecko Epiphany/1.2.5",
	"Mozilla/5.0 (X11; U; Linux i586; en-US; rv:1.7.3) Gecko/20040924 Epiphany/1.4.4 (Ubuntu)",
	"Mozilla/5.0 (X11; U; Linux; i686; en-US; rv:1.6) Gecko Galeon/1.3.14",
	"Konqueror/3.0-rc4; (Konqueror/3.0-rc4; i686 Linux;;datecode)",
	"Mozilla/5.0 (compatible; Konqueror/3.3; Linux 2.6.8-gentoo-r3; X11;",
	"Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.6) Gecko/20040614 Firefox/0.8",
	"ELinks/0.9.3 (textmode; Linux 2.6.9-kanotix-8 i686; 127x41)",
	"ELinks (0.4pre5; Linux 2.6.10-ac7 i686; 80x33)",
	"Links (2.1pre15; Linux 2.4.26 i686; 158x61)",
	"Links/0.9.1 (Linux 2.4.24; i386;)",
	"MSIE (MSIE 6.0; X11; Linux; i686) Opera 7.23",
	"Lynx/2.8.5rel.1 libwww-FM/2.14 SSL-MM/1.4.1 GNUTLS/0.8.12",
	"Links (2.1pre15; FreeBSD 5.3-RELEASE i386; 196x84)",
	"Mozilla/5.0 (X11; U; FreeBSD; i386; en-US; rv:1.7) Gecko",
	"Mozilla/4.77 [en] (X11; I; IRIX;64 6.5 IP30)",
	"Mozilla/4.8 [en] (X11; U; SunOS; 5.7 sun4u)",
	"Mozilla/3.0 (compatible; NetPositive/2.1.1; BeOS)",
);

/*$LIST_SCREEN_RESOLUTIONS = array(
	"800x600",
	"1024x768",
	"1280x800",
	"1280x1024",
	"1600x1200",
	"1680x1050",
);
*/

/*
$LIST_SCREEN_COLORS = array(
	"16",
	"24",
	"32",
);
*/


$LIST_STAT_RANDOM_USERS = array(
	1 => 1,
	2 => 2,
	5 => 5,
	10 => 10,
	25 => 25,
	100 => 100,
);

$LIST_STAT_RANDOM_VISITS = array(
	1 => "1",
	10 => "1 .. 10",
	100 => "1 .. 100",
	1000 => "1 .. 1000",
);

$DATA_STAT_RANDOM = array(
	'fields' => array (
		'users' => array (
			'type' => LiskType::TYPE_LIST,
			'object' => 'def_stat_random_users',
			'label' => "Random Users"
		),
		'visits' => array (
			'type' => LiskType::TYPE_LIST,
			'object' => 'def_stat_random_visits',
			'label' => "Random Visits per User"
		),
		'from' => array (
			'type' => LiskType::TYPE_LIST,
			'object' => 'def_stat_times'
		),
	)
);

// :: SPIDERS - search ::
//	"Googlebot 2.1 (New version)" useragent="Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)" appname="" appversion="" platform="" vendor="" vendorsub=""/>
//	"Googlebot 2.1 (Older Version)" useragent="Googlebot/2.1 (+http://www.googlebot.com/bot.html)" appname="" appversion="" platform="" vendor="" vendorsub=""/>
//	"Msnbot 1.0 (current version)" useragent="msnbot/1.0 (+http://search.msn.com/msnbot.htm)" appname="" appversion="" platform="" vendor="" vendorsub=""/>
//	"Msnbot 0.11 (beta version)" useragent="msnbot/0.11 (+http://search.msn.com/msnbot.htm)" appname="" appversion="" platform="" vendor="" vendorsub=""/>
//	"Yahoo Slurp" useragent="Mozilla/5.0 (compatible; Yahoo! Slurp; http://help.yahoo.com/help/us/ysearch/slurp)" appname="" appversion="" platform="" vendor="" vendorsub=""/>
//	"Ask Jeeves/Teoma" useragent="Mozilla/2.0 (compatible; Ask Jeeves/Teoma)" appname="" appversion="" platform="" vendor="" vendorsub=""/>


// :: SPIDERS - misc ::
//<useragent description="gulperbot" useragent="Gulper Web Bot 0.2.4 (www.ecsl.cs.sunysb.edu/~maxim/cgi-bin/Link/GulperBot)" appname="" appversion="" platform="" vendor="" vendorsub=""/>
//<useragent description="Email Wolf" useragent="EmailWolf 1.00" appname="" appversion="" platform="" vendor="" vendorsub=""/>
//<useragent description="grub client" useragent="grub-client-1.5.3; (grub-client-1.5.3; Crawl your own stuff with http://grub.org)" appname="" appversion="" platform="" vendor="" vendorsub=""/>
//<useragent description="download demon" useragent="Download Demon/3.5.0.11" appname="" appversion="" platform="" vendor="" vendorsub=""/>
//<useragent description="omni web" useragent="OmniWeb/2.7-beta-3 OWF/1.0" appname="" appversion="" platform="" vendor="" vendorsub=""/>
//<useragent description="winHTTP" useragent="SearchExpress" appname="" appversion="" platform="" vendor="" vendorsub=""/>
//<useragent description="ms url control" useragent="Microsoft URL Control - 6.00.8862" appname="" appversion="" platform="" vendor="" vendorsub=""/>
//<useragent description=":: source: techpatterns.com ::" useragent="" appname="" appversion="" platform="" vendor="http://techpatterns.com/forums/about304.html" vendorsub=""/>

//Not detected country

define('FILTER_VISIT_ORGANIC', 1);
define('FILTER_VISIT_DIRECT', 2);
define('FILTER_VISIT_ADWORD', 3);
define('FILTER_VISIT_REFERRER', 4);

$LIST_FILTER_VISIT = array(
	FILTER_VISIT_ORGANIC => 'Organic',
	FILTER_VISIT_DIRECT => 'Direct entrance',
	FILTER_VISIT_ADWORD => 'Adwords',
	FILTER_VISIT_REFERRER => 'Referrer',
);

$DATA_FILTER_VISIT = array(
	'fields' => array(
		'filter' => array(
			'type' => LiskType::TYPE_LIST,
			'object' => 'def_filter_visit',
			'add_values' => array(
				'' => '- All -',
			)
		),
	)
);

$LIST_KEYWORDS_REPORT_TYPE = array(
	FILTER_VISIT_ORGANIC => 'Organic',
	FILTER_VISIT_ADWORD => 'Adwords',
);

$DATA_KEYWORDS_REPORT = array(
	'fields' => array(
		'start' => array(
			'type' => LiskType::TYPE_DATE,
			'min_year' => 2010,
			'max_year' => 1 + date('Y'),
		),
		'end' => array(
			'type' => LiskType::TYPE_DATE,
			'min_year' => 2010,
			'max_year' => 1 + date('Y'),
		),
		'type' => array(
			'type' => LiskType::TYPE_LIST,
			'object' => 'def_keywords_report_type',
			'add_values' => array(
				'' => '- All -',
			)
		),
		'sort_by' => array(
			'type' => LiskType::TYPE_LIST,
			'object' => array(
				'keyword' => 'Keyword',
				'visits' => 'Visits',
				'contact_pages' => 'Contact pages',
				'leads' => 'Leads',
			),
		),
	)
);

class CMSStatVisit
{
	public $VisitorRules;

	public $chartFileSuffix; // add to http chart file src to reload chart file every request
	
	private $filterVisits;
	private $keywordsReport;
	
	function __construct()
	{
		$this->chartFileSuffix = '?unique='.uniqid('u');
		
		$this->InitFilterVisits();
		$this->InitKeywordsReport();
	}

	function RenderTechData()
	{
		GLOBAL $App, $Db, $Parser, $Auth;

		$cond = "date >= '" . date('Y-m-d', time() - @$_GET['cond']) . "'";
        $subj = null;
        
		switch ($_GET['view'])
		{
			case "os":
				$query = array ();
				$k = 0;
				foreach ($GLOBALS['LIST_STAT_TECH_OS_'] as $key => $value) {
					if ($k) $query[] = "(LOCATE('$key', bw) > 0) * $k";
					$k ++;
				}
				$query = "SELECT GREATEST(" . implode(", ", $query) . ")  AS subj, COUNT(id) AS visits FROM stat_visits WHERE $cond GROUP BY subj ORDER BY visits DESC";
				$subj = 'LIST_STAT_TECH_OS';
				$listLabel = 'OS name';
			break;

			case "browser":
				$query = array ();
				$k = 0;
				foreach ($GLOBALS['LIST_STAT_TECH_BROWSERS_'] as $key => $value) {
					if ($k) $query[] = "(LOCATE('$key', bw) > 0) * $k";
					$k ++;
				}
				$query = "SELECT GREATEST(" . implode(", ", $query) . ")  AS subj, COUNT(id) AS visits FROM stat_visits WHERE $cond GROUP BY subj ORDER BY visits DESC";
				$subj = 'LIST_STAT_TECH_BROWSERS';
				$listLabel = 'Browser name';
			break;

			case "color":
				$query = "SELECT screen_color AS subj, COUNT(id) AS visits FROM stat_visits WHERE $cond GROUP BY subj ORDER BY visits DESC";
				$listLabel = 'Display Colors';
			break;

			case "resolution":
				$query = "SELECT screen_size AS subj, COUNT(id) AS visits FROM stat_visits WHERE $cond GROUP BY subj ORDER BY visits DESC";
				$listLabel = 'Display resolution';
			break;

		}

		$values = $Db->query($query);
		$max = 0;
		
		if ($values)
		{
			foreach ($values as $value) $max += $value['visits'];
			foreach ($values as $key => $value)
			{
				if ($subj) $values[$key]['subj'] = $GLOBALS[$subj][$value['subj']];
				$values[$key]['visits_'] = round($value['visits'] * 1000 / $max) / 10;
				$values[$key]['n'] = $key + 1;
			}
		}


		$width = 500;
		$height = 300;
		$filename = "files/chart/{$Auth->user['id']}.png";
		
		$App->Load('libchart', 'mod');
		Libchart::Prepare();
		
		$chart = new PieChart($width, $height);

    	$dataSet = new XYDataSet();
    	if (Utils::IsArray($values))
    	{
    	    foreach ($values as $key=>$value)
    	    {
    			$dataSet->addPoint(new Point($value['subj'].' ('.$value['visits'].')', $value['visits']));
    		}
    	}
    	$chart->setDataSet($dataSet);
	    
    	$chart->setTitle($listLabel);
    	$chart->render($App->sysRoot.$filename);
    	chmod($App->sysRoot.$filename, 0666);
    	
    	Libchart::Restore();

		$chart = "<img src=\"{$App->httpRoot}{$filename}{$this->chartFileSuffix}\" width=\"$width\" height=\"$height\" />";

		$GLOBALS['form_action'] = 'tech';

		$Parser->SetListDecoration('ListTD1','ListTD2');
		$Parser->SetCaptionVariables(array(
			'list_label'	=> $listLabel
		));
		$rezList = $Parser->MakeList($values, 'modules/stat_visit/tech_data', 'list');

		return $Parser->MakeView(array (
			'list'	=> $rezList,
			'chart'	=> $chart
		),'modules/stat_visit/tech_data', 'view');
	}

	function GetCountryByIp($ip)
	{
		GLOBAL $Db;

		$ip_array = explode(".",$ip);
		$ip = ($ip_array[0] * pow(256,3)) +
		   ($ip_array[1] * pow(256,2)) +
		   ($ip_array[2] * 256) +
			$ip_array[3];

		$country = $Db->Get("$ip >= ip_from AND $ip <= ip_to",'country_id','stat_visit_ip2country');
		return $country;
	}

	function __GetDailyData($date)
	{
		GLOBAL $Db;

		$totalOnSiteSecond = 0;
		
		$filterCond = $this->GetCondFilterVisits();
		
		//query statistics
		$rows = $Db->query("
			SELECT
				substring(v.date, 12, 8) as time,
				c.short_name as country_short, c.name as country_name,
				COUNT(v2.id) as tot_visits,
				v.id, v.ip, v.page, v.login, v.visitor_id, v.referrer, v.visitor_id,
				v.pages_browsed, v.date as entrance_time, v.last_visit_time as exit_time,
				v.is_processed, v.search_engine, v.search_engine_keyword, v.comment, v.a_contact_page, v.a_lead
			FROM stat_visits as v
			LEFT JOIN stat_visits as v2 ON v2.visitor_id=v.visitor_id
			LEFT JOIN stat_visit_countries c ON c.id=v.country
			WHERE substring(v.date, 1, 10)='$date' AND v2.visitor_id=v.visitor_id $filterCond
			GROUP BY v.id
			ORDER BY v.date DESC
		");
        
		$totalPages = 0;
		
		if	(Utils::IsArray($rows))
		{
			// init Visitor Rules
			$this->__InitVisitorRules();

			// total site visits
			$totalVisits = sizeof($rows);

			// calculate uniqui visitors
			$uniqueVisitors = array();
			foreach ($rows as $key=>$row)
			{
				if (!in_array($row['visitor_id'],$uniqueVisitors))
				{
					$uniqueVisitors[] = $row['visitor_id'];
				}
			}
			// total unique visitors
			$totalVisitors = sizeof($uniqueVisitors);

			// calculate time on site
			foreach ($rows as $key=>$row)
			{
				$onSiteSeconds = strtotime($row['exit_time'])-strtotime($row['entrance_time']);
				$totalOnSiteSecond += $onSiteSeconds;
				$rows[$key]['visit_time'] = ($onSiteSeconds>0) ? Format::TimeLength($onSiteSeconds,'short') : 'n/a';
				// if more then 1 pages browsed we can add avg. time of page visit but i do nto so that
			}

			// calculate total pages browsed
			foreach ($rows as $row)
			{
				$totalPages += $row['pages_browsed'];
			}

			foreach	($rows as $key=>$row)
			{
				// !!!! Process data
				$rows[$key] = $this->__ProcessVisitData($row);

				if (!strlen(trim($row['login']))) unset($rows[$key]['login']);
			}

			// Format results
			foreach ($rows as $key=>$row)
			{
				$rows[$key]['referrer_url'] = $this->MakeReferrerUrl($row['referrer']);
				$rows[$key]['referrer_name'] = Utils::GetDomainName($row['referrer']);
				
				$enterPage = Utils::RemoveDomainName($row['page']);
				$enterPage = Utils::RemoveSessionId($enterPage);
				$rows[$key]['enter_page'] = $enterPage;
				
				$cs = strtolower($row['country_short']);
				if (!strlen($cs))  $cs = 'xx';
				$rows[$key]['country_short'] = $cs;
			}
		}
		else
		{
			$totalVisits 	= 0;
			$totalVisitors	= 0;
			$totalPages		= 0;
		}

		return array(
			'rows'	   => $rows,
			'visits'   => $totalVisits,
			'visitors' => $totalVisitors,
			'pages'	   => $totalPages,
			'time'	   => $totalOnSiteSecond
		);
	}
	
	private function MakeReferrerUrl($referrer)
	{
		$url = $referrer;
		if (substr($url, 0, 5) != 'http:' && substr($url, 0, 6) != 'https:' )
		{
			$url = 'http://'.$url;
		}
		
		return $url;
	}
	
	function __ProcessVisitData($info)
	{
		GLOBAL $Db,$STAT_VISIT_SEARCH_ENGINES;

		if ($info['is_processed'] == 1) return $info;

		// process Search Engines
		$referrer = $info['referrer'];
		$refDomain = Utils::GetDomainName($referrer);
        $se = '';
        $keyword = '';
        
		// the problem is the domain is co.uk or other... fix in future... with regexp
		foreach ($STAT_VISIT_SEARCH_ENGINES as $key=>$row)
		{
			if (preg_match($row['regexp'], $refDomain))
			{
			    $matches = array();
				$se = $key;
				if (preg_match('/[\?\&]'.$row['keyword_param'].'\=([^\&]*)/', $referrer, $matches))
				{
					$keyword = trim($matches[1]);
					$keyword = urldecode($keyword);
				}
			}
		}

		// Process Visitor Rules
		$comment = array();
		if (Utils::IsArray($this->VisitorRules->values)) 
		{
			foreach ($this->VisitorRules->values as $rule)
			{
				if (false !== strpos($info[$rule['cond_type']], $rule['cond_value']))
				{
					$comment[] = $rule['name'];
				}
			}
		}
		$comment = implode('<br>', $comment);
		if (strlen($comment)) $comment .= '<br>';
		
		$organic = 0;
		if ((strlen($se) > 0) && (false === strpos($info['page'], 'gclid=')))
		{
			$organic =  1;
		}
		
		$adwords = 0;
		if (false !== strpos($info['page'], 'gclid='))
		{
			$adwords = 1;
		}
 
		// update record
		$Db->Update('id='.$info['id'], array(
			'is_processed'			=> 1,
			'search_engine'			=> $se,
			'search_engine_keyword'	=> $keyword,
			'comment'				=> $comment,
			'is_organic'			=> $organic,
			'is_adwords'			=> $adwords
		), 'stat_visits');

		$info['is_processed']			= 1;
		$info['search_engine']			= $se;
		$info['search_engine_keyword'] 	= $keyword;
		$info['comment']				= $comment;
		$info['is_organic']				= $organic;
		$info['is_adwords']				= $adwords;

		return $info;
	}
	
	private function InitKeywordsReport()
	{
		$this->keywordsReport =& $_SESSION['stat_visit_keywords_report'];

		//init default
		if (!Utils::IsArray($this->keywordsReport))
		{
			//2 prev monthes
			$tsStart = mktime(0, 0, 0, date('m')-2, 1, date('Y'));
			$this->keywordsReport['start'] = date('Y-m-d', $tsStart);

			$this->keywordsReport['end'] = date('Y-m-d');
			
			$this->keywordsReport['type'] = null;
			$this->keywordsReport['sort_by'] = null;
		}
		
		$pa = isset($_POST['action']) ? $_POST['action'] : null;
		if ($pa == 'keywords_report')
		{
			GLOBAL $App;
			$App->Load('date', 'type');
			
			$this->keywordsReport['start'] = T_date::GetValueFromHash('start', $_POST);
			$this->keywordsReport['end'] = T_date::GetValueFromHash('end', $_POST);
			
			$this->keywordsReport['type'] = $_POST['type'];
			$this->keywordsReport['sort_by'] = $_POST['sort_by'];
		}
	}
	
	function RenderKeywordsReport()
	{
		GLOBAL $Parser, $Db;
		
		//form
		$di = new Data('keywords_report');
		$di->value = $this->keywordsReport;
		$form = $Parser->MakeForm($di,  'modules/stat_visit/keywords_report',  'form');
		
		$qStart = Database::Escape($this->keywordsReport['start'].' 00:00:00');
		$qEnd = Database::Escape($this->keywordsReport['end'].' 23:59:59');
		
		$typeCond = '';
		if ($this->keywordsReport['type'] == FILTER_VISIT_ORGANIC)
		{
			$typeCond = " AND is_organic=1 ";
		}
		elseif ($this->keywordsReport['type'] == FILTER_VISIT_ADWORD)
		{
			$typeCond = " AND is_adwords=1 ";
		}
		
		switch ($this->keywordsReport['sort_by'])
		{
			case 'keyword':
				$orderBy = 'keyword, count DESC';
				break;
				
			case 'visits':
				$orderBy = 'count DESC, keyword';
				break;
				
			case 'contact_pages':
				$orderBy = 'count_contact_page DESC, keyword';
				break;
				
			case 'leads':
				$orderBy = 'count_lead DESC, keyword';
				break;
				
			default:
				$orderBy = 'count DESC, keyword';
				break;
		}
		
		$sql = "
			SELECT search_engine_keyword AS keyword, COUNT(id) AS count, 
				SUM(a_contact_page) AS count_contact_page,
				SUM(a_lead) AS count_lead
			FROM stat_visits
			WHERE date>=$qStart AND date<=$qEnd AND search_engine_keyword != '' $typeCond
			GROUP BY search_engine_keyword
			ORDER BY $orderBy
		";
		$rows = $Db->Query($sql);
		
		if (Utils::IsArray($rows))
		{
			foreach ($rows as &$row)
			{
				$row['percent_contact_page'] = 0;
				$row['percent_lead'] = 0;
				
				if ($row['count'] > 0)
				{
					$row['percent_contact_page'] = round($row['count_contact_page'] / $row['count'] * 100);
					$row['percent_lead'] = round($row['count_lead'] / $row['count'] * 100);
				}
				
			}
		}
		
		//report
		$Parser->SetListDecoration('ListTD1', 'ListTD2');
		$report = $Parser->MakeList($rows, 'modules/stat_visit/keywords_report',  'report');
		
		
		$view = array(
			'form' => $form,
			'report' => $report,
		);
		
		return $Parser->MakeView($view,  'modules/stat_visit/keywords_report',  'view');
	}
	
	function RenderDaily()
	{
		GLOBAL $Parser,$App,$Auth;

		//calendar view
		$App->Load('calendar', 'class');
		$url = Navigation::AddGetVariable(array('action' => 'view', 'view' => 'daily'));
		$Calendar = new Calendar($url, 'stat_visits');
		$Calendar->tplName = 'modules/stat_visit/calendar';

		// Init values
		$avgPages = 0;
		$avgTime = 0;
		$statInfo = '';
        $viewLiveStatus = null;
        
		// get data
		$total = $this->__GetDailyData($Calendar->currentDate);
		$rows = $total['rows'];

		if (Utils::IsArray($rows))
		{

			// add Live Status if today
			if (date('Y-m-d')==$Calendar->currentDate)
			{
				$viewLiveStatus = '';
				foreach ($rows as $key=>$row)
				{
					if ((time() - strtotime($row['exit_time'])) < STAT_VISIT_LIVE_TIME)
					{
						$status = 'on';
					}
					else
					{
						$status = 'off';
					}
					$rows[$key]['live_status'] = $status;
				}
			}

			// work on notes
			foreach ($rows as $key=>$row)
			{
				if ($row['search_engine'] != '')
				{
					$rows[$key]['search_engine_info'] = "<b>{$row['search_engine']}</b> - {$row['search_engine_keyword']}<br>";
				}
				
				if ($row['a_contact_page'])
				{
					$rows[$key]['action_contact_page'] = '<div class="sv-a-contact-page">contact page</div>';
				}
				
				if ($row['a_lead'])
				{
					$rows[$key]['action_lead'] = '<div class="sv-a-lead">lead</div>';
				}
			}

			$Parser->SetListDecoration('ListTD1', 'ListTD2');
			$Parser->SetCaptionVariables(array(
				'view_live_status'	=> $viewLiveStatus
			));
			$statInfo =	$Parser->MakeList($rows, 'modules/stat_visit/daily', 'day_statistics');

			$avgPages 	= round($total['pages'] / $total['visits'], 2);
			$avgTime	= Format::TimeLength(round($total['time'] / $total['visits'], 0), 'short');

		}

		$summaryInfo = $Parser->MakeView(
			array(
				'total_visits'		=>	$total['visits'],
				'total_pages'		=>	$total['pages'],
				'total_visitors'	=>	$total['visitors'],
				'avg_pages'			=>	$avgPages,
				'avg_time'			=>	$avgTime,
			), 
			'modules/stat_visit/daily', 
			'summary_info'
		);
		
		return $Parser->MakeView(
			array(
				'calendar' => $Calendar->Render(),
				'statistics' =>	$statInfo,
				'summary_info' => $summaryInfo,
				'filter' => $this->RenderFilterVisits()
			), 
			'modules/stat_visit/daily', 
			'view'
		);

	}
	
	private function GetCondFilterVisits()
	{
		
		switch ($this->filterVisits)
		{
			case FILTER_VISIT_ORGANIC:
				return " AND v.is_organic=1 ";
				break;	
				
			case FILTER_VISIT_DIRECT:
				return " AND v.referrer='' AND v.is_organic=0 AND v.is_adwords=0 ";
				break;
				
			case FILTER_VISIT_ADWORD:
				return " AND v.is_adwords=1 ";
				break;
				
			case FILTER_VISIT_REFERRER:
				return " AND v.referrer!='' AND v.is_organic=0 AND v.is_adwords=0 ";
				break;
		}
		
		
	}
	
	private function InitFilterVisits()
	{
		$this->filterVisits = isset($_GET['filter']) ? $_GET['filter'] : null;
	}
	
	private function RenderFilterVisits()
	{
		GLOBAL $Parser;
		$di = new Data('filter_visit');
		
		$di->value['filter'] = $this->filterVisits;
		
		$caption = array(
			'url' => Navigation::AddGetVariable(array('z' => 'x')),
		);
		
		$Parser->SetCaptionVariables($caption);
		return $Parser->MakeForm($di, 'modules/stat_visit/filter_visits', 'view');
	}

	function RenderMonthly()
	{
		GLOBAL $App,$Db,$Parser;
		
		//calendar view
		$App->Load('calendar','class');
		$Calendar 			=  new Calendar('?action=view&view=monthly', 'stat_visits');
		$Calendar->tplName	=	'modules/stat_visit/calendar_monthly';



		$rows = $Db->Query("
			select '$Calendar->year' as year, '$Calendar->month' as month,
					substring(date, 9, 2) as day, count(v.id) as visits
			FROM stat_visits v
			WHERE substring(date, 1, 7)='$Calendar->year-$Calendar->month'
			GROUP BY day
		");
		
		// fucking visitors
		$rows4 = $Db->Query("select substring(date, 9, 2) as day FROM stat_visits WHERE substring(date, 1, 7)='$Calendar->year-$Calendar->month' GROUP BY day, visitor_id");

		// fucking history
		$rows2 = $Db->Query("
			SELECT substring(h.date, 9, 2) as day, count(h.id) as pages
			FROM stat_visits_history AS h
			RIGHT JOIN stat_visits AS v ON v.id=h.visit_id
			WHERE substring(h.date, 1, 7)='$Calendar->year-$Calendar->month'
			GROUP BY day
		");
		
		$totalVisits = 0;
		$totalPages = 0;
		$totalVisitors = 0;
		$totalTime = 0;

		$rez = array();
		if ($rows && is_array($rows))
		{
			foreach($rows as $row)
			{
				$rez[$row['day']]=array();
				$rez[$row['day']]['visits']=$row['visits'];
				$rez[$row['day']]['year']=$row['year'];
				$rez[$row['day']]['month']=$row['month'];
				$totalVisits += $row['visits'];
				
				
				$total = $this->__GetDailyData("{$Calendar->year}-{$Calendar->month}-{$row['day']}");
				$totalTime += $total['time'];
				
			}
		}
			
		if ($rows2 && is_array($rows2))
		{
			foreach($rows2 as $row)
			{
				$rez[$row['day']]['pages']=$row['pages'];
				$totalPages += $row['pages'];
			}
		}
		if ($rows4 && is_array($rows4))
		{
			foreach ($rows4 as $row)
			{
				if (!isset($rez[$row['day']]['visitors'])) $rez[$row['day']]['visitors'] = 1;
				else $rez[$row['day']]['visitors'] += 1;
			}
		}

		foreach($rez as $key=>$row)
		{
			$rez[$key]['day'] = $key;
			$rez[$key]['apages'] = round($row['pages']/$row['visits'],2);
			$rez[$key]['avisits'] = round($row['visits']/$row['visitors'],2);
			$rez[$key]['month_name'] = date('F', mktime(0,0,0, $Calendar->month));
			$totalVisitors += $rez[$key]['visitors'];
		}

		$Parser->SetListDecoration('ListTD1','ListTD2');
		$statInfo=$Parser->MakeList($rez, 'modules/stat_visit/monthly','month_statistics');
		
		
		$summaryInfo = $Parser->MakeView(array(
			'TOTAL_VISITS'		=>	$totalVisits,
			'TOTAL_PAGES'		=>	$totalPages,
			'TOTAL_VISITORS'	=>	$totalVisitors,
			'TOTAL_AVISITS'		=>	$totalVisitors?round($totalVisits / $totalVisitors, 2): 0,
			'TOTAL_APAGES'		=>	$totalVisitors?round($totalPages / $totalVisitors, 2): 0,
			'AVG_PAGES'			=>	$totalVisits? round($totalPages / $totalVisits, 2) : 0,
			'AVG_TIME'			=>	$totalVisits? Format::TimeLength(round($totalTime / $totalVisits, 0),'short') : 0,
		),'modules/stat_visit/monthly','summary_info');

		return $Parser->MakeView(array(
			'MONTH_SELECT'		=> $Calendar->Render(),
			//'MONTH_PREV_NEXT'	=> 	$monthPrevNext,
			'STATISTICS'		=>	$statInfo,
			'SUMMARY_INFO'		=> 	$summaryInfo,
		),'modules/stat_visit/monthly','view');
	}

	function RenderAnnual()
	{
		GLOBAL $App,$Db,$Parser;

		//calendar view
		$App->Load('calendar','class');
		$Calendar 			= 	new Calendar('?action=view&view=annual','stat_visits');
		$Calendar->tplName	=	'modules/stat_visit/calendar_annual';

		//common month by month
		$rows = $Db->Query("select '$Calendar->year' as year, substring(date, 6, 2) as month, count(v.id) as visits FROM stat_visits v WHERE substring(date, 1, 4)='$Calendar->year' GROUP BY month");

		// fucking visitors
		$rows4 = $Db->Query("select substring(date, 6, 2) as month FROM stat_visits WHERE substring(date, 1, 4)='$Calendar->year' GROUP BY month, visitor_id");

		// fucking history
		$rows2 = $Db->Query("select substring(date, 6, 2) as month, count(id) as pages FROM stat_visits_history WHERE substring(date, 1, 4)='$Calendar->year' GROUP BY month");
		
		$totalVisits = 0;
		$totalPages = 0;
		$totalVisitors = 0;
		
		$rez=array();
		if ($rows && is_array($rows))
		{
			foreach($rows as $row)
			{
				$rez[$row['month']] = array();
				$rez[$row['month']]['visits'] = $row['visits'];
				$rez[$row['month']]['year'] = $row['year'];
				$totalVisits += $row['visits'];
			}
		}
		if ($rows2 && is_array($rows2))
		{
			foreach($rows2 as $row)
			{
				$rez[$row['month']]['pages']=$row['pages'];
				$totalPages += $row['pages'];
			}
		}
		if ($rows4 && is_array($rows4))
		{
			foreach ($rows4 as $row)
			{
				if (!isset($rez[$row['month']]['visitors'])) $rez[$row['month']]['visitors'] = 1;
				else $rez[$row['month']]['visitors'] += 1;
			}
		}
		foreach($rez as $key=>$row)
		{
			$rez[$key]['month'] = $key;
			$rez[$key]['apages']= round($row['pages']/$row['visits'],2);
			$rez[$key]['avisits']=round($row['visits']/$row['visitors'],2);
			$rez[$key]['month_name'] = date('F',mktime(0,0,0,$key));
			$totalVisitors += $rez[$key]['visitors'];
		}

		$Parser->SetListDecoration('ListTD1','ListTD2');
		$statInfo=$Parser->MakeList($rez, 'modules/stat_visit/annual','year_statistics');

		$summaryInfo	=	$Parser->MakeView(array(
			'TOTAL_VISITS'		=>	$totalVisits,
			'TOTAL_PAGES'		=>	$totalPages,
			'TOTAL_VISITORS'	=>	$totalVisitors,
			'TOTAL_AVISITS'		=>	$totalVisitors?round($totalVisits / $totalVisitors, 2): 0,
			'TOTAL_APAGES'		=>	$totalVisitors?round($totalPages / $totalVisitors, 2): 0,
			'AVG_PAGES'			=>	$totalVisits? round($totalPages / $totalVisits, 2) : 0,
			'AVG_TIME'			=>	0,
		),'modules/stat_visit/annual','summary_info');


		return $Parser->MakeView(array(
			'STATISTICS'		=> $statInfo,
			'SUMMARY_INFO'		=> $summaryInfo,
			'CALENDAR'			=> $Calendar->Render()
		),'modules/stat_visit/annual','view');
	}

	function RenderBounceRate()
	{
		GLOBAL $Db, $Parser, $LIST_STAT_BOUNCE;

		$time = strtotime(date('Y-m-d'));

		if (!isset($_GET['cond'])) $_GET['cond'] = 7 * DAY;

		$cond = "date >= '" . date('Y-m-d', $time - $_GET['cond']) . "'";

		$values = $Db->Query("SELECT COUNT(id) AS visits FROM stat_visits_history WHERE $cond GROUP BY visit_id ORDER BY visits");
		$max = count($values);

		$visits = array();
		$result = array();
		
		if ($values)
		{
    		foreach ($values as $key=>$value)
    		{
    			$value = $value['visits'];
    			if ($value < 10) $visits[$value - 1] += 1;
    			elseif ($value < 15) $visits[9] += 1;
    			elseif ($value < 20) $visits[10] += 1;
    			else $visits[11] += 1;
    		}
	    }
        
	    if (Utils::IsArray($visits))
        {
    		foreach($LIST_STAT_BOUNCE as $key=>$value)
    		{
    			$visit = (int)$visits[$key];
    			$result[] = array (
    				'views' => $value,
    				'rate' => round($visit * 100 / $max),
    				'width' => round($visit * 400 / $max),
    				'visitors' => $visit
    			);
    		}
        }

		$Data = new Data('stat_bounce');
		$Data->value = $_GET;
		$GLOBALS['form_action'] = 'bounce';

		$Parser->SetListDecoration('ListTD1', 'ListTD2');
		return $Parser->MakeList($result, 'modules/stat_visit/bounce', 'bounce_list');
	}

	function RenderVisitTrends()
	{
		GLOBAL $App, $Db, $Parser, $Auth;

		$this->currentBookmark = 'Trends';

		if (!strlen(@$_GET['cond'])) $_GET['cond']= 30 * DAY;
		$period = $_GET['cond'];
		$time = strtotime('now');

		$cond = "date >= '" . date('Y-m-d', $time - $period) . " 00:00:00' AND date <= '" . date('Y-m-d', $time) . " 23:59:59'";

		switch ($period)
		{
			case 7*DAY:
			case 14*DAY:
				$values = $Db->Query("SELECT SUBSTRING(date, 1, 10) AS subdate, visit_id, COUNT(id) AS visits FROM stat_visits_history WHERE $cond GROUP BY subdate, visit_id ORDER BY date");
				$dateFormat = "D, d M";
				break;
			case 30 * DAY:
				$values = $Db->Query("SELECT SUBSTRING(date, 1, 10) AS subdate, visit_id, COUNT(id) AS visits FROM stat_visits_history WHERE $cond GROUP BY subdate, visit_id ORDER BY date");
				$dateFormat = "D, d M";
				break;
			case 92 * DAY:
				$values = $Db->Query("SELECT SUBSTRING(date, 1, 10) AS subdate, visit_id, COUNT(id) AS visits FROM stat_visits_history WHERE $cond GROUP BY subdate, visit_id ORDER BY date");
				$dateFormat = "D, d M";
				break;

			case 183 * DAY:
				$values = $Db->Query("SELECT SUBSTRING(date, 1, 10) AS subdate, visit_id, COUNT(id) AS visits FROM stat_visits_history WHERE $cond GROUP BY subdate, visit_id ORDER BY date");
				$dateFormat = "D, d M";
				break;
		}

		// preobraqzovanie poluchennogo massiva posechenii
		$temp = array ();
		if ($values) foreach ($values as $value)
		{
		    if (!isset($temp[$value['subdate']])) $temp[$value['subdate']] = array('visitors' => 0, 'visits' => 0);
			$temp[$value['subdate']]['visitors'] += 1;
			$temp[$value['subdate']]['visits'] += $value['visits'];
		}

		$result = array ();
		foreach ($temp as $key => $value)
		{
			$result[] = array (
				'date'		=> Format::Date($key,$dateFormat),
				'visitors'	=> $value['visitors'],
				'visits'	=> $value['visits']
			);
		}

		$Data = new Data('stat_trends');
		$Data->value = $_GET;
		$GLOBALS['form_action'] = 'trends';

		$chart_values = array();

		switch ($period)
		{
			case 7*DAY:
				for ($i = -6; $i <= 0; $i++)
				{
					$label = date('D', $time + $i * DAY + 1);
					$subdate = date('Y-m-d', $time + $i * DAY + 1);
					$chart_values[] = array(
						'x' => $label,
						'y' => (int)@$temp[$subdate]['visitors']
					);
				}
				break;

			case 14*DAY:
				for ($i = -14; $i <= 0; $i ++)
				{
					$label = date('m/d', $time + $i * DAY + 1);
					$subdate = date('Y-m-d', $time + $i * DAY + 1);
					$chart_values[] = array (
						'x' => $label,
						'y' => (int)@$temp[$subdate]['visitors']
					);
				}
				break;

			case 30*DAY:
				for ($i = -30; $i <= 0; $i ++)
				{
					$label = date('m/d', $time + $i * DAY + 1);
					$subdate = date('Y-m-d', $time + $i * DAY + 1);
					$chart_values[] = array (
						'x' => $label,
						'y' => (int)@$temp[$subdate]['visitors']
					);
				}
				break;

			case 92*DAY:
				for ($i = -92; $i <= 0; $i ++)
				{
					$label = date('m/d', $time + $i * DAY + 1);
					$subdate = date('Y-m-d', $time + $i * DAY + 1);
					$chart_values[] = array(
						'x' => $label,
						'y' => (int)@$temp[$subdate]['visitors']
					);
				}
				break;

			case 183*DAY:
				for ($i = -183; $i <= 0; $i ++)
				{
					$label = date('m/d', $time + $i * DAY + 1);
					$subdate = date('Y-m-d', $time + $i * DAY + 1);
					$chart_values[] = array (
						'x' => $label,
						'y' => (int)@$temp[$subdate]['visitors']
					);
				}
				break;
		}

		$width = 560;
		$height = 300;
		$filename = 'files/chart/'.$Auth->user['id'].'.png';

		$App->Load('libchart', 'mod');
		Libchart::Prepare();
		
		$chart = new VerticalBarChart($width, $height);

    	$dataSet = new XYDataSet();
	    foreach($chart_values as $value)
	    {
			$dataSet->addPoint(new Point($value['x'], $value['y']));
		}
    	$chart->setDataSet($dataSet);
	    
    	$chart->setTitle('Visits / Dates');
    	$chart->render($App->sysRoot.$filename);
    	chmod($App->sysRoot.$filename, 0666);
    	
    	Libchart::Restore();

		return $Parser->MakeView(array (
			'list' => $Parser->MakeList($result, 'modules/stat_visit/trend_visits', 'trends_list'),
			'chart' => "<img src=\"{$App->httpRoot}{$filename}{$this->chartFileSuffix}\" width=\"$width\" height=\"$height\" />"
		), 'modules/stat_visit/trend_visits', 'view');
	}


	function MakeDetails ($visitor_id = null, $visit_id = null)
	{
		GLOBAL $Parser,$Db;
		$visitor_id = $visitor_id ? $visitor_id : $_GET['visitor_id'];
		$visit_id = $visit_id ? $visit_id : $_GET['visit_id'];

		$this->Page->SetGlobalTemplate('0');
		$this->Page->LoadTemplate('modules/stat_visit/visit_details');

		$visits=$Db->Select("visitor_id='".$visitor_id."'", 'id desc', '' ,'stat_visits');

		$siv=sizeof($visits);

		$this->Page->Tpl->SetCurrentBlock('visits_row');
		foreach ($visits as $key=>$row)
		{
			$urls=$Db->Select("visit_id=$row[id]",'id ASC','','stat_visits_history');
			$rez=array();
			foreach ($urls as $url)
			{
				$rez2['page']=$url['page'];
				$rez2['time']=$url['date'];
				$rez[]=$rez2;
			}

			foreach ($rez as $key2=>$row2)
			{
				$time=Format::TimeLength((strtotime($rez[$key2+1]['time'])-strtotime($row2['time'])),'short');
				$rez3[$key2]['time']=$time;
				$rez3[$key2]['page']=Utils::RemoveSessionId($row2['page']);
			}

			$last_rec=sizeof($rez3);
			$rez3[$last_rec-1]['time']='';

			$pages=$Parser->MakeList($rez3,'modules/stat_visit/visit_details','pages');

			$this->Page->Tpl->SetVariable(array(
				'VISIT_N'	=> $siv-$key,
				'DATE'		=> $row[date],
				'PAGES'		=> $pages
			));
			$this->Page->Tpl->ParseCurrentBlock();
		}

		$this->Page->Tpl->SetCurrentBlock('visits');
		$this->Page->Tpl->SetVariable('visit_info',$this->RenderVisitInfoBlock($visit_id));
		$this->Page->Tpl->ParseCurrentBlock();
		return $this->Page->Tpl->Get();
	}

	function ReportPeriod ($startDate, $endDate = null)
	{
		GLOBAL $Parser, $Db;
		
		$startDate = $startDate.' 00:00:01';

		$endDate = $endDate ? $endDate : Date('Y-m-d');
		$endDate.= ' 23:23:59';

		$sql = "
		SELECT
			COUNT(v.id) as visits, SUM(v.pages_browsed) as pages_browsed
		FROM
			stat_visits as v
		WHERE
			'$startDate' < v.date AND v.date < '$endDate'";
		$rows = $Db->query($sql);
		$totalPages = $rows[0]['pages_browsed'];
		$totalVisits = $rows[0]['visits'];

		$visitors = $Db->Query(
			"SELECT COUNT(DISTINCT visitor_id) as visitors FROM `stat_visits`
			WHERE date>='$startDate' AND date <= '$endDate'"
		);
		$totalVisitors = $visitors[0]['visitors'];


		$summaryInfo	=	$Parser->MakeView(array(
			'TOTAL_VISITS'		=>	$totalVisits,
			'TOTAL_PAGES'		=>	$totalPages,
			'TOTAL_VISITORS'	=>	$totalVisitors,
		),'modules/stat_visit/report','summary_info');


		$result = $Parser->MakeView(array(
			'SUMMARY_INFO'		=> 	$summaryInfo,
			'startdate'			=>	Format::Date($startDate,'M-d, Y '),
			'enddate'			=>	Format::Date($endDate,'M-d, Y ')
		),'modules/stat_visit/report','view_by_period');

		$startDate 	= strtotime ($startDate);
		$endDate 	= strtotime ($endDate);
		$curDate 	= $startDate;

		$substr = $endDate - $startDate;
		$daysCount = round($substr / (60 * 60 * 24)) + 1;

		for ($i = 0; $i < $daysCount; $i++)
		{
			$result .= $this->ReportDaily(date('Y-m-d', $curDate));
			$curDate = $this->__GetNextDay($curDate);
		}

		return $result;
	}



	function ReportDaily ($date = null)
	{
		GLOBAL $Parser;
		$date = $date ? $date : Date('Y-m-d');

		$day = strtotime($date);

		$total = $this->__GetDailyData($date);
		$rows = $total['rows'];
        $details = '';
        
		if (Utils::IsArray($rows))
		{
			foreach ($rows as $row)
			{
				$details .= $this->__ReportVisitDetails($row, $day);
			}

			$statInfo =	$Parser->MakeList($rows, 'modules/stat_visit/report','day_statistics');

			$summaryInfo	=	$Parser->MakeView(array(
				'TOTAL_VISITS'		=>	$total['visits'] ? $total['visits'] : 0,
				'TOTAL_PAGES'		=>	$total['pages'] ? $total['pages'] : 0,
				'TOTAL_VISITORS'	=>	$total['visitors'] ? $total['visitors'] : 0,
				'AVG_PAGES'			=>	round($total['pages'] / $total['visits'], 2),
				'AVG_TIME'			=>	Format::TimeLength(round($total['time'] / $total['visits'], 0), 'short'),
			),'modules/stat_visit/report', 'summary_info');
		}

		$details_out = $Parser->MakeView(array(
			'details_block' => $details
		),'modules/stat_visit/report', 'details_view');

		return $Parser->MakeView(array(
			'DAY_ANCHOR'		=> $day,
			'DATE'				=>	$date,
			'SUMMARY_INFO'		=> 	$summaryInfo,
			'STATISTICS'		=>	$statInfo,
			'DETAILS'			=>  $details_out
		),'modules/stat_visit/report','view');
	}

	function __ReportVisitDetails ($visit, $day)
	{
		GLOBAL $Parser,$Db;

		$urls = $Db->Select("visit_id = {$visit['id']}", 'id ASC', null, 'stat_visits_history');
		$rez = array();

		foreach ($urls as $url)
		{
			$rez2['page']=$url['page'];
			$rez2['time']=$url['date'];
			$rez[] = $rez2;
		}

		foreach ($rez as $key2=>$row2)
		{
		    if (isset($rez[$key2+1])) $time = Format::TimeLength((strtotime($rez[$key2+1]['time'])-strtotime($row2['time'])), 'short');
			else $time = '';
			
			$rez3[$key2]['time']=$time;
			$rez3[$key2]['page']=Utils::RemoveSessionId($row2['page']);
		}
		$last_rec = sizeof($rez3);
		$rez3[$last_rec-1]['time']='';


		$visit['day_anchor']	= $day;
		$visit['visit_pages']	= $Parser->MakeList($rez3,'modules/stat_visit/visit_details','pages');
		$visit['anchor']		= $visit['id'];
		return $Parser->MakeView($visit, 'modules/stat_visit/visit_details', 'total');
	}

	function __GetNextDay ($date)
	{
		$date = getdate($date);
		return mktime(
	  	 	$date['hours'],
	  		$date['minutes'],
	  		$date['seconds'],
	    	$date['mon'],
	    	$date['mday']+1,
	    	$date['year']
    	);
	}

	function __InitVisitorRules()
	{
		$this->VisitorRules = new Data('stat_visit_rule');
		$this->VisitorRules->Select();
	}
}

?>