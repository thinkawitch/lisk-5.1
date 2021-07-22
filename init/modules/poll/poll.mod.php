<?php

$GLOBALS['POLL_MODULE_INFO'] = array(
	'name'			=> 'Poll',
	'sys_name'		=> LiskModule::MODULE_POLL,
	'version'		=> '5.0',
	'description'	=> 'Poll Module',
	'object_name'	=> 'Poll',
	'multiinstance'	=> false,
	'ss_integrated'	=> false
);

define('POLL_VOTE_MODE_RANDOM', 0);
define('POLL_VOTE_MODE_FIRST', 1);

$LIST_POLL_VOTE_MODE = array(
	POLL_VOTE_MODE_RANDOM => 'Random active poll',
	POLL_VOTE_MODE_FIRST  => 'First active poll',
);

define('POLL_VOTE_FREQ_ONCE',    0);
define('POLL_VOTE_FREQ_DAILY',   1);
define('POLL_VOTE_FREQ_WEEKLY',  2);
define('POLL_VOTE_FREQ_MONTHLY', 3);

$LIST_POLL_VOTE_FREQUENCY = array(
	POLL_VOTE_FREQ_ONCE    => 'Once',
	POLL_VOTE_FREQ_DAILY   => 'Daily',
	POLL_VOTE_FREQ_WEEKLY  => 'Weekly',
	POLL_VOTE_FREQ_MONTHLY => 'Monthly',
);

/**
 * Poll module main class
 *
 */
class Poll extends LiskModule
{
	/**
	 * Poll DataItem name
	 *
	 * @var string
	 */
	public $confDIPollName;

	/**
	 * Answer DataItem name
	 *
	 * @var string
	 */
	public $confDIAnswerName;

	/**
	 * Frequency of voiting
	 *
	 * @var integer
	 */
	public $confVoteFrequency;

	/**
	 * @var integer
	 */
	public $confVoteMode;

	/**
	 * Guestbook base url
	 *
	 * @var string
	 */
	public $confBaseUrl;

	/**
	 * Templates path
	 *
	 * @var string
	 */
	public $tplPath = 'modules/poll_';

	/**
	 * Id used to render,vote,etc
	 *
	 * @var integer
	 */
	public $currentPollId;

	/**
	 * Constructor
	 *
	 * @param integer $instanceId
	 * @return Poll
	 */
	function __construct($instanceId=null)
	{
		$this->name = LiskModule::MODULE_POLL;
		if ($instanceId!=null) $this->Init($instanceId);
	}

	/**
	 * Initialize module
	 *
	 */
	function Init($instanceId)
	{
		parent::Init($instanceId);

		$this->tplPath .= $instanceId.'/';

		$this->version = $GLOBALS['POLL_MODULE_INFO']['version'];

		$this->confBaseUrl       = $this->config['base_url'];
		$this->confDIPollName 	 = $this->config['di_name_poll'];
		$this->confDIAnswerName  = $this->config['di_name_answer'];
		$this->confVoteFrequency = $this->config['vote_frequency'];
		$this->confVoteMode		 = $this->config['vote_mode'];

		$this->Debug('di_name_poll', $this->confDIPollName);
		$this->Debug('di_name_answer', $this->confDIAnswerName);
		$this->Debug('vote_frequency', $this->confVoteFrequency);
		$this->Debug('vote_mode', $this->confVoteMode);
	}

	/**
	 * Save settings
	 *
	 */
	function SaveSettings()
	{
		GLOBAL $Db;
		$this->config['base_url'] 		= $this->confBaseUrl;
		$this->config['di_name_poll']	= $this->confDIPollName;
		$this->config['di_name_anwer']	= $this->confDIAnswerName;
		$this->config['vote_frequency']	= $this->confVoteFrequency;
		$this->config['vote_mode']		= $this->confVoteMode;
		$Db->Update('id='.$this->iid, array(
			'config' => serialize($this->config)
		), 'sys_modules');
	}

	/**
	 * Install module
	 *
	 * @param integer $instanceId
	 * @param array $params
	 */
	public function InstallConfigure($instanceId, $params)
	{
		$GLOBALS['App']->LoadModule('modules/poll/poll.install.mod.php', 1);
		installPollModule($instanceId, $params['path']);
	}

	/**
	 * Uninstall module
	 *
	 */
	public function Uninstall()
	{
		$GLOBALS['App']->LoadModule('modules/poll/poll.install.mod.php', 1);
		uninstallPollModule($this->iid);
		parent::Uninstall();
	}

	/**
	 * Proccess page parameters to define if poll parameters are present
	 *
	 * @return string
	 */
	public function ProcessParameters()
	{
		GLOBAL $Page;

		$toDo = 'form';
		$parameters = $Page->parameters;

		if (Utils::IsArray($parameters))
		{
			foreach ($parameters as $k=>$pname)
			{
				if ($pname == 'poll_result')
				{
					$toDo = 'result';
					$this->currentPollId = $parameters[$k+1];

				}
				elseif ($pname == 'poll_vote')
				{
					$toDo='vote';
					$this->currentPollId = $parameters[$k+1];
				}
			}
		}

		if (!$this->currentPollId)
		{
			$alreadyVoted=$this->SetCurrentPoll();
			if ($alreadyVoted)
			{
				$toDo = 'result';
			}
		}

		return $toDo;
	}

	/**
	 * Set current poll if no specified via request
	 *
	 * @return boolean
	 */
	private function SetCurrentPoll()
	{
		GLOBAL $Db;
		$DI = Data::Create($this->confDIPollName);
		if ($this->confVoteMode==POLL_VOTE_MODE_RANDOM)
		{
			// select all active polls
			$polls = $DI->SelectValues('is_active=1');
			$notVotedPolls = array();
			if (Utils::IsArray($polls))
			{
				// get not voted polls
				foreach ($polls as $poll)
				{
					if (!$this->IsPollVoted($poll['poll_uniq_id']))
					{
						$notVotedPolls[] = $poll['id'];
					}
				}
			}

			// if there are no voted polls select one of them
			// else view results
			if (Utils::IsArray($notVotedPolls))
			{
				$poll = Utils::Randomize($notVotedPolls, 1);
				$this->currentPollId = $poll[0];
				return false;
			}
			else
			{
				$poll = Utils::Randomize($polls, 1);
				$this->currentPollId = $poll[0]['id'];
				return true;
			}
		}
		elseif ($this->confVoteMode==POLL_VOTE_MODE_FIRST)
		{
			// latest poll only mode
			// get latest poll
			$Db->SetLimit(0, 1);
			$poll = $DI->SelectValues('is_active=1', null, 'date DESC');
			$Db->ResetLimit();
			$poll = $poll[0];

			// if voted - result else vote form
			if ($this->IsPollVoted($poll['poll_uniq_id']))
			{
				$this->currentPollId=$poll['id'];
				return true;
			}
			else
			{
				$this->currentPollId=$poll['id'];
				return false;
			}
		}
	}

	/**
	 * Enter description here...
	 *
	 * @param integer $uniqueId
	 * @return boolean
	 */
	private function IsPollVoted($uniqueId)
	{
		return @$_COOKIE['poll_'.$uniqueId] != null;
	}

	/**
	 * Store poll vote
	 *
	 * @param integer $uniqueId
	 */
	private function SetPollVoted($uniqueId)
	{
		$time = time();
		switch($this->confVoteFrequency)
		{
			case POLL_VOTE_FREQ_DAILY:
				$time += 60*60*24;
				break;
				
			case POLL_VOTE_FREQ_WEEKLY:
				$time += 60*60*24*7;
				break;
				
			case POLL_VOTE_FREQ_MONTHLY:
				$time += 60*60*24*30;
				break;
				
			default:
				$time += 60*60*24*365;
				break;
		}
		Utils::SetCookie('poll_'.$uniqueId, 1, $time);
	}

	/**
	 * Make a vote
	 *
	 */
	function PollVote()
	{
        $answer = @$_POST['answer'];

		$DIPoll = Data::Create($this->confDIPollName);
		$DIAnswer = Data::Create($this->confDIAnswerName);

		$poll = $DIPoll->GetValue('id='.$this->currentPollId);
		if ($poll['is_active'] && !$this->IsPollVoted($poll['poll_uniq_id']))
		{
			$DIAnswer->Update("id='$answer'", array(
				'votes'	=> 'sql:votes+1'
			));
			$this->SetPollVoted($poll['poll_uniq_id']);
		}
		
		StatActionHandler::Set('STAT_OBJECT_POLL', 'STAT_OBJECT_POLL_VOTE');
	}

	/**
	 * Render module
	 *
	 */
	public function Render()
	{
		GLOBAL $App;
		$toDo = $this->ProcessParameters();

		switch($toDo)
		{
			case 'form':
				$html = $this->RenderPollForm();
				break;
				
			case 'result':
				$html = $this->RenderPollResult();
				break;
				
			case 'vote':
				$html = $this->PollVote();
				$url = str_replace('poll_vote/', '', $_SERVER['REQUEST_URI']);
				if (!strlen($url)) $url = $App->httpRoot;
				Navigation::Jump($url);
				break;
		}

		return $html;
	}

	/**
	 * Render poll form
	 *
	 * @return string
	 */
	public function RenderPollForm()
	{
		GLOBAL $App,$Parser,$Page;
		$DIPoll = Data::Create($this->confDIPollName);
		$DIAnswer = Data::Create($this->confDIAnswerName);

		$DIPoll->Get('id='.$this->currentPollId);

		$DIAnswer->Select('poll_id='.$this->currentPollId);

		$DIPoll->value['poll_options'] = $Parser->MakeList($DIAnswer, $this->tplPath.'poll', 'poll_options_list');

		$DIPoll->value['url_vote'] = $Page->GetPageUrl()."poll_vote/{$this->currentPollId}/";
		$DIPoll->value['url_result'] = $Page->GetPageUrl()."poll_result/{$this->currentPollId}/";

		return $Parser->MakeView($DIPoll,$this->tplPath.'poll', 'poll_block_not_voted');
	}

	/**
	 * Render poll result
	 *
	 * @return string
	 */
	public function RenderPollResult()
	{
		GLOBAL $Parser, $App, $Page;
		$DIPoll = Data::Create($this->confDIPollName);
		$DIAnswer = Data::Create($this->confDIAnswerName);

		$DIPoll->Get('id='.$this->currentPollId);

		// get answers for current poll
		$answers = $DIAnswer->SelectValues('poll_id='.$this->currentPollId);

		// get total number of answers
		$totalAnswers = $this->CountAnswers($answers);

		// add % variable to answers array
		if ($totalAnswers > 0)
		{
			$answers = $this->CountAnswersPercents($answers, $totalAnswers);
		}

		$linkToVote = ($this->IsPollVoted($DIPoll->value['poll_uniq_id'])) ? null : $this->currentPollId;
		if ($linkToVote)
		{
			$DIPoll->value['url_vote'] = $Page->GetPageUrl();
		}

		$DIPoll->value['poll_graphic'] = $Parser->MakeList($answers, $this->tplPath.'poll', 'poll_graphic');

		$Parser->SetCaptionVariables(array(
			'name' 			=> $DIPoll->value['name'],
			'date'			=> Format::Date($DIPoll->value['date'], 'Y-m-d'),
			'total_votes' 	=> $totalAnswers,
			//'vote_id'		=> $voteId,
		));

		return $Parser->MakeView($DIPoll, $this->tplPath.'poll', 'poll_block_voted');
	}
	
	/**
	 * Count total votes for poll
	 *
	 * @param array $answers
	 * @return integer
	 */
	public function CountAnswers($answers)
	{
		$total = 0;
		if (Utils::IsArray($answers))
		{
			foreach ($answers as $answer)
			{
				$total += $answer['votes'];
			}
		}
		return  $total;
	}
	
	/**
	 * Count answers percents
	 *
	 * @param array $answers
	 * @param integer $totalAnswers
	 * @return array
	 */
	public function CountAnswersPercents($answers, $totalAnswers)
	{
		if (Utils::IsArray($answers))
		{
			foreach ($answers as $k=>$answer)
			{
				$answers[$k]['percentage'] = round($answer['votes'] / $totalAnswers * 100).'%';
			}
		}
		return $answers;
	}
	
	/**
	 * Render poll for snippet
	 *
	 * @return string
	 */
	public function RenderSnippetPoll()
	{
		return $this->Render();
	}
	
	/**
	 * Run snippet method
	 *
	 * @param array $params
	 * @return string
	 */
	public function Snippet($params)
	{
		switch (strtolower($params['name']))
		{
			case 'poll':
				return $this->RenderSnippetPoll();
				break;
		}
		return '';
	}
	
	/**
	 * Get all available snippets of module
	 *
	 * @return array
	 */
	public function AvailableSnippets()
	{
		return array(
			'poll'	=> array(
				'description'	=> 'Poll block snippet, insert into any page',
				'code'			=> '<lisk:snippet src="module" instanceId="[iid]" name="poll" />'
			),
		);
	}
}


/**
 * DataItem for poll
 *
 */
class PollDI extends Data
{
	public $instanceId;

	function __construct($diName, $initFields=true)
	{
		parent::__construct($diName, $initFields, 'Obj_PollDI_di_'.$diName);

		//define object's module instanceId
		$arr = explode('_', $diName);
		$this->instanceId = end($arr);
	}

	function TgerBeforeInsert(&$insertValues)
	{
		$insertValues['poll_uniq_id'] = uniqid(null);
		return true;
	}

	function TgerAfterDelete($cond, $values)
	{
		$dataItem = Data::Create('dyn_poll_answer');
		foreach ($values as $row)
		{
			$dataItem->Delete('poll_id='.$row['id']);
		}
		return true;
	}
}

?>