<?php $IEM = $tpl->Get('IEM'); ?><span class="HelpText" onMouseOut="HideHelp('<?php if(isset($GLOBALS['ResendTipName'])) print $GLOBALS['ResendTipName']; ?>');" onMouseOver="ShowHelp('<?php if(isset($GLOBALS['ResendTipName'])) print $GLOBALS['ResendTipName']; ?>', '<?php print GetLang('ResendTipHeading'); ?>', '<?php print GetLang('ResendTipInfo'); ?>');"><a
href="index.php?Page=Send&Action=ViewSendErrors&Job=<?php if(isset($GLOBALS['Job'])) print $GLOBALS['Job']; ?>"><img
src='images/m_newsletters_flag.gif' border='0'></a></span><br /><div id="<?php if(isset($GLOBALS['ResendTipName'])) print $GLOBALS['ResendTipName']; ?>" style="display: none;"></div>




