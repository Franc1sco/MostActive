<nav class="navbar navbar-default navbar-fixed-top" data-spy="affix" data-offset-top="485">
	<div class="container">
		<div class="navbar-header">
			<button type="button" class="navbar-toggle collapsed" data-ng-click="isCollapsed1 = !isCollapsed1"
			data-ng-class="{'navbar-open': isCollapsed1}">
				<span class="icon-bar">
				</span>
				<span class="icon-bar">
				</span>
				<span class="icon-bar">
				</span>
			</button>
			<a class="navbar-brand" href="/">
				<span>
					<?php 
					if ($siteLogo != "")
					{
						echo '<img src="' .$siteLogo. '"> ' .$siteTitle. '';
					}
					?>
				</span>
			</a>
		</div>
		<div class="collapse navbar-collapse" data-uib-collapse="!isCollapsed1">
			<ul class="nav navbar-nav navbar-left">
				<li>
					<a href="/">
					Most Active
					</a>
				</li>
			</ul>
		</div>
	</div>
</nav>
