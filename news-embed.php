<?php if (file_exists('../news/embed.php')) { // Only include if there is a file to embed ?>
<div class="pm-window news-embed" data-newsid="<?= date("Ymd") ?>">
	<h3><button class="closebutton" tabindex="-1"><i class="fa fa-times-circle"></i></button><button class="minimizebutton" tabindex="-1"><i class="fa fa-minus-circle"></i></button>News</h3>
	<div class="pm-log" style="max-height:none">
		<?php include '../news/embed.php'; ?>
	</div>
</div>
<?php } ?>