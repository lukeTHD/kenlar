<?php $IEM = $tpl->Get('IEM'); ?><div style="display: <?php if(isset($GLOBALS['DisplayStatsLinkList'])) print $GLOBALS['DisplayStatsLinkList']; ?>;">
<select name="chooselink" id="chooselink"><option value="a"><?php print GetLang('AnyLink'); ?></option><?php if(isset($GLOBALS['StatsLinkList'])) print $GLOBALS['StatsLinkList']; ?></select>
<input type="button" value="<?php print GetLang('Go'); ?>" class="body" onclick="ChangeLink(<?php if(isset($GLOBALS['CurrentPage'])) print $GLOBALS['CurrentPage']; ?>,'<?php if(isset($GLOBALS['SortColumn'])) print $GLOBALS['SortColumn']; ?>','<?php if(isset($GLOBALS['SortDirection'])) print $GLOBALS['SortDirection']; ?>');">
</div>



