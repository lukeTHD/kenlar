<?php $IEM = $tpl->Get('IEM'); ?><span class="HelpText" onMouseOut="HideHelp('<?php if(isset($GLOBALS['TipName'])) print $GLOBALS['TipName']; ?>');" onMouseOver="ShowQuickHelp('<?php if(isset($GLOBALS['TipName'])) print $GLOBALS['TipName']; ?>', '<?php print GetLang('AlreadySentTo_Heading'); ?>', '<?php if(isset($GLOBALS['LastSentTip'])) print $GLOBALS['LastSentTip']; ?>');"><?php if(isset($GLOBALS['LastSent'])) print $GLOBALS['LastSent']; ?>
<?php if(isset($GLOBALS['LastSentTip_Extra'])) print $GLOBALS['LastSentTip_Extra']; ?></span><br /><div id="<?php if(isset($GLOBALS['TipName'])) print $GLOBALS['TipName']; ?>" style="display: none;"></div>




