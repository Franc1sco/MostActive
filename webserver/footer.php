<!-- FOOTER -->
<div class="well" style="text-align: right">
	<p>
		coded with
		<span class="glyphicon glyphicon-heart" aria-hidden="false"></span>
		by 
		<!-- You can edit everything as you want, but please put the credits in any kind -->
		<a href="http://shanapu.de">
		shanapu
		</a>
	<p>
		<font size="2">
			<b>
			Love goes out to
			</b>
			<!-- You can edit everything as you want, but please put the credits in any kind -->
			popoklopsi, Franc1sco, the sourcemod & bootstrap dev teams
		</font>
	</p>
</div>

<!-- BACK TO TOP BUTTON -->
<span id="top-link-block" class="hidden">
	<a href="#top" class="well well-sm" onclick="$(\'html,body\').animate({scrollTop:0},\'slow\');return false;">
		<i class="glyphicon glyphicon-chevron-up"></i> Back to Top
	</a>
</span>

<script>
		if ( ($(window).height() + 500) < $(document).height() )
		{
			$('#top-link-block').removeClass('hidden').affix(
			{
				offset: {top:500}
			});
		}
</script>