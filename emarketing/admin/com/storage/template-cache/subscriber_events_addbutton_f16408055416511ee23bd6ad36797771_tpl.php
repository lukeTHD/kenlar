<?php $IEM = $tpl->Get('IEM'); ?><input class="FormButton" type="button" value="<?php print GetLang('LogEvent'); ?>" style="width: 100px;" class="thickbox" onclick="resetForm(<?php if(isset($GLOBALS['SubscriberID'])) print $GLOBALS['SubscriberID']; ?>,0,<?php if(isset($GLOBALS['ListID'])) print $GLOBALS['ListID']; ?>);tb_show('<?php print GetLang('EventAddTitle'); ?>','#TB_inline?a&height=420&width=500&inlineId=eventAddFormDiv');">



