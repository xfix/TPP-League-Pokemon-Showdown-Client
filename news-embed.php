<?php 
if (!file_exists('../news/embed.php')) { 
    return; // Only include if there is a file to embed 
}
ob_start(); //start output buffering
?>
<div class="pm-window news-embed" data-newsid="<?= date("Ymd") ?>">
	<h3><button class="closebutton" tabindex="-1"><i class="fa fa-times-circle"></i></button><button class="minimizebutton" tabindex="-1"><i class="fa fa-minus-circle"></i></button>News</h3>
	<div class="pm-log" style="max-height:none">
		<?php 
		    ob_start(); //stack the buffer for this include
		    include '../news/embed.php'; 
		    $newslen = ob_get_length(); //grah the length of what was included
		    ob_end_flush(); //flush the included file into the first buffer
		?>
	</div>
</div>
<?php 
if ($newslen > 0) { // There was news to invlude
    ob_end_flush(); //flush the output buffer to output
} else {
    ob_end_clean(); //discard the output buffer entirely
}