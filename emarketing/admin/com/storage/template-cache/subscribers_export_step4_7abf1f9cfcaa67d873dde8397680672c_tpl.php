<?php $IEM = $tpl->Get('IEM'); ?><script>
	$(function() {
		$('input#startExportSubscriber').click(function(event) {
			tb_show('', 'index.php?Page=Subscribers&Action=Export&SubAction=ExportIFrame&keepThis=true&TB_iframe=tue&height=260&width=450&modal=true', '');
			event.preventDefault();
			event.stopPropagation();
		});
	});
</script>
<table cellspacing="0" cellpadding="0" width="100%" align="center">
	<tr>
		<td class="Heading1">
			<?php print GetLang('Subscribers_Export_Step4'); ?>
		</td>
	</tr>
	<tr>
		<td class="body pageinfo">
			<p>
				<?php if(isset($GLOBALS['SubscribersReport'])) print $GLOBALS['SubscribersReport']; ?>
				<input id="startExportSubscriber" type="button" value="<?php print GetLang('ExportStart'); ?>" class="FormButton_wide" style="margin-top: 5px;"/>
			</p>
		</td>
	</tr>
</table>




