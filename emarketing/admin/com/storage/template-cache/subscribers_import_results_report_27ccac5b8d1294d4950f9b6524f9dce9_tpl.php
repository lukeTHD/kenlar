<?php $IEM = $tpl->Get('IEM'); ?><table cellspacing="0" cellpadding="0" width="100%" align="center">
	<tr>
		<td class="Heading1">
			<?php print GetLang('ImportResults_Heading'); ?>
		</td>
	</tr>
	<tr>
		<td class="body pageinfo">
			<p>
				<?php if(isset($GLOBALS['Message'])) print $GLOBALS['Message']; ?>
			</p>
		</td>
	</tr>
	<tr>
		<td>
			<?php if(isset($GLOBALS['Report'])) print $GLOBALS['Report']; ?>
		</td>
	</tr>
</table>
<script>
	function ShowReport(reporttype) {
		var link = 'index.php?Page=Subscribers&Action=Import&SubAction=View_Report&ReportType=' + reporttype;

		var top = screen.height / 2 - (230);
		var left = screen.width / 2 - (250);

		window.open(link,"reportWin","left=" + left + ",top="+top+",toolbar=false,status=no,directories=false,menubar=false,scrollbars=false,resizable=false,copyhistory=false,width=500,height=460");
	}
</script>




