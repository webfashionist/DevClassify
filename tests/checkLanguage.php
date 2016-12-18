<?php

require_once "../src/classes/ProgrammingLanguage.class.php";
$ProgrammingLanguage = new ProgrammingLanguage();

// Start of code
$time = microtime(true); // Gets microseconds

?><!DOCTYPE html>
<html>
	<head>
		<title></title>

	</head>
	<body style="background-color:#EFEFEF;font-family:Calibri,Verdana,Helvetica,sans-serif;">



		<h1 style="color:#16566E;text-align:center;">Programming Language Detection</h1>
		
		<form action="checkLanguage.php" method="post" style="margin:0 auto;padding:20px;background-color:#FFF;width:400px;">
			<textarea name="code" style="border:#999 solid 1px;font-family:monospace;height:300px;width:100%;min-width:100%;max-width:100%;"><?php 
			// display code in textarea
			echo (isset($_POST["code"]) && $_POST["code"] ? htmlentities($_POST["code"]) : ''); 

			?></textarea> 
			<br>
			<input type="submit" value="Check code" style="display:block;padding:10px 15px;background-color:#16566E;color:#FFF;cursor:pointer;border:none;width:100%;">
		</form>


		<?php
		if(isset($_POST["code"]) && $_POST["code"]) {

			// move the code to the class
			$result = $ProgrammingLanguage->check($_POST["code"]);
			// get the probabilities for the given code
			$prob = $result->probabilities;

			// save the code in tests/source/*.txt for log and test purposes
			$filename = "code_extension_".$result->extension."_".time()."_".mt_rand(100,999).".txt";
			if (!is_dir('source/')) {
				// directory doesn't exist, create it
				mkdir('source/');
			}
			// path exists - save code
			file_put_contents('source/' . $filename, $_POST["code"]);


			// display results:
		?>
		<div style="margin:20px auto 10px auto;padding:20px;background-color:#FFF;width:400px;">
			<b>PHP:</b> <?php echo $prob->php; ?>%<br>
			<b>JavaScript:</b> <?php echo $prob->js; ?>%<br>
			<b>jQuery:</b> <?php echo $prob->jquery; ?>%<br>
			<b>HTML:</b> <?php echo $prob->html; ?>%<br>
			<b>XML:</b> <?php echo $prob->xml; ?>%<br>
			<b>CSS:</b> <?php echo $prob->css; ?>%<br>
			<b>JSON:</b> <?php echo $prob->json; ?>%<br>
			<b>SQL:</b> <?php echo $prob->sql; ?>%<br>
			<hr>
			Recommended file extension: <b><?php echo $result->extension; ?></b>
		</div>
		<?php
		}
		?>


		<div style="margin:20px auto;text-align:center;">Time Elapsed: <?php echo round((microtime(true) - $time), 2); ?></div>
	</body>
</html>
