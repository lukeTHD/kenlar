<?php
/**
 * IEM implementation of Interspire Template
 *
 * This adds IEM-specific functionality to the template class.
 *
 * @package @package interspire.iem.lib.iem
 */

/**
 * Include base directory
 */
require_once(IEM_PATH . '/ext/interspire_template/class.template.php');

/**
 * IEM_InterspireStash class
 *
 * The IEM Interspire Stash class is designed to handle caching of commonly used variables
 * that require presistant storage. It is designed so that it can restore it self
 * in the event that the cache data files were deleted.
 *
 * Limitation at this stage is that it can only hold data of up to ~16.7 million bytes (ie. characters)
 *
 * @package @package interspire.iem.lib.iem
 */
class IEM_InterspireTemplate extends InterspireTemplate
{
	/**
	 * CONSTRUCTOR
	 * @return IEM_InterspireTemplate Returns an instance of this object
	 */
	public function __construct()
	{
		parent::__construct();
		array_push($this->AllowedFunctions, 'intval', 'strtolower', 'strreplace', 'getjson', 'abs', 'key');
	}




	/**
	 * ParseTemplate
	 * This is the master 'parsing' function. It reads in the template file and runs through all the parsing functions in order.
	 *
	 * @param String $name Template to be parsed
	 * @param Boolean $return Toggles whether the parsed template code will be echo'ed out directly or returned by the function.
	 *
	 * @return Mixed Can be either void or a String
	 *
	 * @see LoadTemplateFile
	 * @see ParseForeach
	 * @see ParseSet
	 * @see ParseIf
	 * @see ParseVariables
	 * @see TemplateData
	 */
	public function ParseTemplate($name=null, $return = false)
	{
		if (!is_null($name)) {
			$name = strtolower($name);
			$name = checkTemplate($name, false);
			$this->SetTemplate($name);
		}

		// ----- Initialize any variables that should be available to the running template
			$tpl = $this;
			$this->Assign('IEM', self::IEM_DefaultVariables(), false);

			if ($name == 'header_popup') {
				$GLOBALS['UsingWYSIWYG'] = '0';
				$user = IEM::getCurrentUser();
				if ($user) {
					if ($user->Get('usewysiwyg') == 1) {
						$GLOBALS['UsingWYSIWYG'] = '1';
					}
				}
			}

			if (!IEM::sessionGet('LicenseError')) {
				if (SENDSTUDIO_SEND_TEST_MODE) {
					$this->Assign('ShowTestModeWarning', true);
					$this->Assign('SendTestWarningMessage', GetLang('Header_Send_TestMode_WarningMessage_User'), false);

					$user = IEM::getCurrentUser();
					if ($user && $user->Admin()) {
						$this->Assign('SendTestWarningMessage', GetLang('Header_Send_TestMode_WarningMessage_Admin'), false);
					}
				}
			}
		// -----

		// File name
		$file = $this->TemplatePath . $this->TemplateFile . '.' . $this->TemplateFileExtension;

		// Check whether or not the template file exists
		if (!file_exists($file)) {
			trigger_error(sprintf(GetLang('ErrCouldntLoadTemplate'), ucwords($name)), E_USER_ERROR);
		}

		$file_hash = md5($file);
		$cacheFile = $this->CachePath . '/' . $this->TemplateFile . '_' . $file_hash . '_' . $this->TemplateFileExtension . '.php';

		if (!file_exists($cacheFile) || filemtime($file) > filemtime($cacheFile)) {
			$this->LoadTemplateFile();
			$this->StripTemplateComments();

			// run through all the template parsing functions
			// TODO: add in event handling to allow modules to hook into the template system
			$this->TemplateData = $this->ParseForeach($this->TemplateData); // foreach is recursive, so we need to use this method of calling it

			$this->ParseIEM();

			$this->ParseAlias();
			$this->ParseCapture();
			$this->ParseCycle();
			$this->ParseIncludes();
			$this->ParseSet();
			$this->ParseIf();
			$this->ParseConfig();
			$this->ParseLanguageVariables();
			$this->ParseHelpLanguageVariables();
			$this->ParseVariables();

			/*
			if ($parseRecursive) {
				$this->ParseLegacy_Recursive();
			}*/
			$this->ParseLegacy_Request();
			$this->ParseLegacy_Global();
			$this->ParseLegacy_LanguageHelp();
			$this->ParseLegacy_Language();

			// this cleans up the code a bit, if there is a closing PHP tag and only whitespace between it and another opening PHP tag,
			// get rid of both of them and let the PHP 'continue'
			$this->TemplateData = preg_replace('#\? >[\n\s\t]*<\?php#sm', '', $this->TemplateData);

			file_put_contents($cacheFile, $this->TemplateData);
		}

		ob_start();
		include($cacheFile);
		$this->TemplateData = ob_get_contents();
		ob_end_clean();

		if ($return) {
			return $this->TemplateData;
		} else {
			echo $this->TemplateData;
		}
	}

	/**
	 * Enter description here...
	 *
	 * @return Void Does not return anything
	 * @todo phpdoc
	 */
	private function IEM_DefaultVariables()
	{
		static $variables = null;

		if (is_null($variables)) {
			$IEM = array(
				'User'				=> GetUser(),
				'ApplicationTitle'	=> GetLang('ApplicationTitle'),
				'PageTitle'			=> GetLang('PageTitle'),
				'CurrentPage'		=> isset($_GET['Page'])? strtolower($_GET['Page']) : ''
			);

			list($IEM['MenuLinks'], $IEM['TextLinks']) = $this->IEM_Menu();
			list($IEM['LicenseError'], $IEM['LicenseMessage']) = ssQmz44Rtt();

			IEM::sessionSet('LicenseError', $IEM['LicenseError']);

			if (!$IEM['LicenseError'] && isset($GLOBALS['ProductEdition'])) {
				$productEdition = '';

				if (defined('SS_NFR')) {
					$productEdition = $GLOBALS['ProductEdition'];
				} else {
					$productEdition = ucfirst(strtolower($GLOBALS['ProductEdition']));
				}

				$IEM['ApplicationTitle'] .= sprintf(GetLang('ApplicationTitleEdition'), $productEdition);
			}

			$variables = $IEM;
		}

		return $variables;
	}


	/**
	 * IEM_Menu
	 * This builds both the nav menu (with the dropdown items) and the text menu links at the top
	 * It gets the main nav items from SendStudio_Functions::GenerateMenuLinks
	 * It gets the text menu items from SendStudio_Functions::GenerateTextMenuLinks
	 *
	 * It will also see if test-mode is enabled (and display an appropriate message)
	 * and also generate the right headers at the top (user is logged in as 'X', the current time is 'Y' etc).
	 *
	 * <b>Do *not* put any "ParseTemplate" calls inside IEM_Menu as you will cause an infinite loop.</b>
	 * "ParseTemplate" calls "IEM_Menu" via IEM_DefaultVariables
	 * Since the header menu has not yet finished building (ie the $menu variable is still null),
	 * calling IEM_Menu at this stage will then call ParseTemplate (which then calls IEM_Menu).
	 *
	 * It returns an array:
	 * - the first item is the main nav menu (contact lists, contacts, email campaigns etc)
	 * - the second item is the text menu links at the top of the page (templates, users/manage account, logout etc)
	 *
	 * @uses SendStudio_Functions::GenerateMenuLinks
	 * @uses SendStudio_Functions::GenerateTextMenuLinks
	 *
	 * @return Array Returns an array containing the main nav menu (the first item of the array) and the text menu items (the second item of the array).
	 */
	private function IEM_Menu()
	{
		static $menu = null;

		// we've already built the menu? just return it.
		if ($menu !== null) {
			return $menu;
		}

		$user = IEM::getCurrentUser();

		// we're not logged in? we don't have a menu so just return empty items.
		if (!$user) {
			$menu = array('', '');
			return $menu;
		}

		// see if there is an upgrade required or problem with the lk.
		if (!isset($_GET['Page']) || strtolower($_GET['Page']) != 'upgradenx') {
			require_once(SENDSTUDIO_API_DIRECTORY . DIRECTORY_SEPARATOR . 'settings.php');
			$settings_api = new Settings_API();
			if ($settings_api->NeedDatabaseUpgrade() && $user->Admin()) {
				header('Location: index.php?Page=upgradenx');
				exit;
			}

			if (IEM::sessionGet('LicenseError')) {
				if (!isset($_GET['Page']) || strtolower($_GET['Page']) != 'settings') {
					header('Location: index.php?Page=Settings');
					exit;
				}
			}
		}

		$nav_menus = '';
		if (!IEM::sessionGet('LicenseError')) {
			$nav_menus = SendStudio_Functions::GenerateMenuLinks();
		}

		$GLOBALS['UsingWYSIWYG'] = '0';
		if ($user->Get('usewysiwyg') == 1) {
			$GLOBALS['UsingWYSIWYG'] = '1';
		}

		$adjustedtime = AdjustTime();

		$GLOBALS['SystemDateTime'] = sprintf(GetLang('UserDateHeader'), AdjustTime($adjustedtime, false, GetLang('UserDateFormat'), true), $user->Get('usertimezone'));

		$name = $user->Get('username');
		$fullname = $user->Get('fullname');
		if ($fullname != '') {
			$name = $fullname;
		}
		$GLOBALS['UserLoggedInAs'] = sprintf(GetLang('LoggedInAs'), htmlentities($name, ENT_QUOTES, SENDSTUDIO_CHARSET));

		$unlimited_emails = $user->Get('unlimitedmaxemails');
		if (!$unlimited_emails) {
			$GLOBALS['TotalEmailCredits'] = sprintf(GetLang('User_Total_CreditsLeft'), SendStudio_Functions::FormatNumber($user->Get('maxemails')));
		}

		$monthly_credits = $user->Get('permonth');
		if ($monthly_credits > 0) {
			$availableCredit = API_USERS::creditAvailableThisMonth($user->GetNewAPI());

			if ($availableCredit !== true) {
				if ($availableCredit === false) {
					$availableCredit = '(' . GetLang('Unknown') . ')';
				} else {
					$availableCredit = SendStudio_Functions::FormatNumber($availableCredit);
				}

				$GLOBALS['MonthlyEmailCredits'] = sprintf(GetLang('User_Monthly_CreditsLeft'), $availableCredit, SendStudio_Functions::FormatNumber($monthly_credits));

				if (!$unlimited_emails) {
					$GLOBALS['MonthlyEmailCredits'] .= '&nbsp;&nbsp;|';
				}
			}
		}

		$textlinks = SendStudio_Functions::GenerateTextMenuLinks();

		$menu = array($nav_menus, $textlinks);

		return $menu;
	}

	/**
	 * ParseHelpLanguabeVariables
	 * This parses help language variable for {$lnghlp.} variables
	 *
	 * @return Void Doesn't return anything
	 *
	 * @see TemplateData
	 */
	public function ParseHelpLanguageVariables()
	{
		if (!preg_match_all('#\{\$lnghlp\.([^}]*)\}#is', $this->TemplateData, $matches)) {
			return;
		}

		foreach ($matches[1] as $index => $value) {
			$helpTip = 	"<span class=\"HelpToolTip\">"
						. '<img src="images/help.gif" alt="?" width="24" height="16" border="0" />'
						. "<span class=\"HelpToolTip_Title\" style=\"display:none;\"><?php print stripslashes(GetLang('{$value}')); ?></span>"
						. "<span class=\"HelpToolTip_Contents\" style=\"display:none;\"><?php print stripslashes(GetLang('HLP_{$value}')); ?></span>"
						. "</span>";

			$this->TemplateData = str_replace($matches[0][$index], $helpTip, $this->TemplateData);
		}
	}

	/**
	 * Enter description here...
	 *
	 * @return Void Does not return anything
	 * @todo phpdoc
	 */
	private function ParseIEM()
	{
		$this->TemplateData = '<?php $IEM = $tpl->Get(\'IEM\'); ?>' . $this->TemplateData;
		$this->TemplateData = str_replace('%%PAGE%%', '<?php print $IEM[\'CurrentPage\']; ?>', $this->TemplateData);
		$this->TemplateData = str_replace('%%PAGE_TITLE%%', '<?php print $IEM[\'PageTitle\']; ?>', $this->TemplateData);
		$this->TemplateData = str_replace('%%GLOBAL_MenuLinks%%', '<?php if(IEM::getCurrentUser()) print $IEM[\'MenuLinks\']; ?>', $this->TemplateData);
		$this->TemplateData = str_replace('%%GLOBAL_TextLinks%%', '<?php if(IEM::getCurrentUser()) print $IEM[\'TextLinks\']; ?>', $this->TemplateData);
		$this->TemplateData = str_replace('%%GLOBAL_ApplicationTitle%%', '<?php print $IEM[\'ApplicationTitle\']; ?>', $this->TemplateData);
	}

	/**
	 * Enter description here...
	 *
	 * @return Void Does not return anything
	 * @todo phpdoc
	 */
	private function ParseLegacy_Recursive()
	{
		if (!preg_match_all('/(?siU)%%TPL_([a-zA-Z0-9_]{1,})%%/', $this->TemplateData, $matches)) {
			return;
		}

		foreach ($matches[0] as $key => $value) {
			$replace = "<?php \$tpl->ParseTemplate('{$matches[1][$key]}'); ?>";
			$this->TemplateData = str_replace($value, $replace, $this->TemplateData);
		}
	}

	/**
	 * Enter description here...
	 *
	 * @return Void Does not return anything
	 * @todo phpdoc
	 */
 	private function ParseLegacy_Request()
	{
		if (!preg_match_all('/(?siU)%%REQUEST_([a-zA-Z0-9_]{1,})%%/', $this->TemplateData, $matches)) {
			return;
		}

		foreach ($matches[0] as $key => $value) {
			$this->TemplateData = str_replace($value, "<?php if(isset(\$_REQUEST['{$matches[1][$key]}'])) print \$_REQUEST['{$matches[1][$key]}']; ?>", $this->TemplateData);
		}
	}

	/**
	 * Enter description here...
	 *
	 * @return Void Does not return anything
	 * @todo phpdoc
	 */
 	private function ParseLegacy_Global()
	{
		if (!preg_match_all('/(?siU)%%GLOBAL_([a-zA-Z0-9_]{1,})%%/', $this->TemplateData, $matches)) {
			return;
		}

		foreach ($matches[0] as $key => $value) {
			$this->TemplateData = str_replace($value, "<?php if(isset(\$GLOBALS['{$matches[1][$key]}'])) print \$GLOBALS['{$matches[1][$key]}']; ?>", $this->TemplateData);
		}
	}

	/**
	 * Enter description here...
	 *
	 * @return Void Does not return anything
	 * @todo phpdoc
	 */
	private function ParseLegacy_Language()
	{
		if (!preg_match_all('/%%LNG_([a-zA-Z0-9_]{1,})%%/', $this->TemplateData, $matches)) {
			return;
		}

		foreach ($matches[0] as $key => $value) {
			$this->TemplateData = str_replace($value, "<?php print GetLang('{$matches[1][$key]}'); ?>", $this->TemplateData);
		}
	}

	/**
	 * Enter description here...
	 *
	 * @return Void Does not return anything
	 * @todo phpdoc
	 */
	private function ParseLegacy_LanguageHelp()
	{
		if (!preg_match_all('/(?siU)(%%LNG_HLP_[a-zA-Z0-9_]{1,}%%)/', $this->TemplateData, $matches)) {
			return;
		}

		foreach ($matches[0] as $key => $value) {
			$tipname = str_replace(array('%%', 'LNG_'), '', $value);
			$tiptitle = str_replace('HLP_', '', $tipname);

			$helpTip = 	"<span class=\"HelpToolTip\">"
						. '<img src="images/help.gif" alt="?" width="24" height="16" border="0" />'
						. "<span class=\"HelpToolTip_Title\" style=\"display:none;\"><?php print stripslashes(GetLang('{$tiptitle}')); ?></span>"
						. "<span class=\"HelpToolTip_Contents\" style=\"display:none;\"><?php print stripslashes(GetLang('{$tipname}')); ?></span>"
						. "</span>";

			$this->TemplateData = str_replace($value, $helpTip, $this->TemplateData);
		}
	}

	/**
	 * Enter description here...
	 * @param String $charset New characterset
	 * @return Void Does not return anything
	 * @todo phpdoc
	 */
	public function SetCharacterSet($charset)
	{
		$this->CharacterSet = $charset;
	}

	/**
	 * Enter description here...
	 *
	 * @return String Returns currently used characterset
	 * @todo phpdoc
	 */
	public function GetCharacterSet()
	{
		return $this->CharacterSet;
	}

	/**
	 * Assign
	 * This sets variables in the $Variables for use in the template files.
	 *
	 * @param Mixed $name The name of the variable to set. If it is an array, it will be used to detemine the depth.
	 * @param Mixed $value The value of the variable to set. Can be any standard PHP variable value (i.e. string, boolean, integer, array, object)
	 * @param Boolean $htmlescape Specify whether or not the templating system needs to escape the variable (Default to InterspireTemplate::$DefaultHtmlEscape)
	 *
	 * @return Void Doesn't return anything
	 * @uses InterspireTemplate::Assign()
	 */
	public function Assign($name, $value, $htmlescape = false)
	{
		parent::Assign($name, $value, $htmlescape);
	}
}
