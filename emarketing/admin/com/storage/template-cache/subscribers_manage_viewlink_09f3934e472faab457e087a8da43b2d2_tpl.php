<?php $IEM = $tpl->Get('IEM'); ?><a href="index.php?Page=Subscribers&Action=View&List=<?php if(isset($GLOBALS['List'])) print $GLOBALS['List']; ?>&id=<?php if(isset($GLOBALS['EditSubscriberID'])) print $GLOBALS['EditSubscriberID']; ?><?php if(isset($GLOBALS['ExtraParameter'])) print $GLOBALS['ExtraParameter']; ?>"><?php print GetLang('View'); ?></a>



