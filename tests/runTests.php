<?php
require_once "../src/classes/ProgrammingLanguage.class.php";
$ProgrammingLanguage = new ProgrammingLanguage();

// Start of code
$time = microtime(true); // Gets microseconds
$code = '';
?><!DOCTYPE html>
<html>
	<head>
		<title></title>

	</head>
	<body style="background-color:#FAFAFA;font-family:Calibri,Verdana,Helvetica,sans-serif;">

	<?php
	$codes = array();
	$path = "source/";
	if ($handle = opendir($path)) {
	    while (false !== ($file = readdir($handle))) {
	        if ('.' === $file) continue;
	        if ('..' === $file) continue;
	        $extension_tmp = explode(".", $file);
	        $extension = $extension_tmp[count($extension_tmp)-1];
	        if($extension === "txt") {
	        	$codes[] = file_get_contents($path.$file);
	        }
	    }
	    closedir($handle);
	}




	if($codes && is_array($codes) && count($codes) > 0) {
		foreach($codes as $code) {


			if($code) {
				?>
				<div style="float:left;padding:20px;width:25%;">
					<div style="background-color:#FFF;padding:10px;">
						<code style="display:block;margin:0px auto;padding:10px;border:#999 solid 1px;overflow:auto;height:200px;width:300px;"><?php echo nl2br(htmlentities($code)); ?></code>
						<br><br>
				<?php
				$result = $ProgrammingLanguage->check($code);
				$prob = $result->probabilities;
				?>
						<div style="margin:0 auto 10px auto;padding:10px;border-top:#EFEFEF solid 1px;width:300px;">
							<b>PHP:</b> <?php echo $prob->php; ?>%<br>
							<b>JavaScript:</b> <?php echo $prob->js; ?>%<br>
							<b>jQuery:</b> <?php echo $prob->jquery; ?>%<br>
							<b>HTML:</b> <?php echo $prob->html; ?>%<br>
							<b>XML:</b> <?php echo $prob->xml; ?>%<br>
							<b>CSS:</b> <?php echo $prob->css; ?>%<br>
							<b>JSON:</b> <?php echo $prob->json; ?>%<br>
							<b>SQL:</b> <?php echo $prob->sql; ?>%<br>
							<b>Bash:</b> <?php echo $prob->sh; ?>%<br>
							<hr>
							Recommended file extension: <b><?php echo $result->extension; ?></b>
						</div>
					</div>
				</div>
				<?php
			}
		}
	}
	?>
		<div style="clear:both;"></div>
		<div style="text-align:center;">Time Elapsed: <?php echo round((microtime(true) - $time), 2); ?>s</div>
	</body>
</html>
