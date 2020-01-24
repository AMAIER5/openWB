<!DOCTYPE html>
<html lang="de">

	<!-- Einstellungen für automatisches Sperren/Entsperren
		 der LP: ein Vorgang pro Tag
	 	 Autor: M. Ortenstein -->
	<head>
		<base href="/openWB/web/">

		<meta charset="UTF-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
		<title>OpenWB</title>
		<meta name="description" content="Control your charge">
		<meta name="keywords" content="html template, css, free, one page, gym, fitness, web design">
		<meta name="author" content="Michael Ortenstein">
		<!-- Favicons (created with http://realfavicongenerator.net/)-->
		<link rel="apple-touch-icon" sizes="57x57" href="img/favicons/apple-touch-icon-57x57.png">
		<link rel="apple-touch-icon" sizes="60x60" href="img/favicons/apple-touch-icon-60x60.png">
		<link rel="icon" type="image/png" href="img/favicons/favicon-32x32.png" sizes="32x32">
		<link rel="icon" type="image/png" href="img/favicons/favicon-16x16.png" sizes="16x16">
		<link rel="manifest" href="manifest.json">
		<link rel="shortcut icon" href="img/favicons/favicon.ico">
		<meta name="msapplication-TileColor" content="#00a8ff">
		<meta name="msapplication-config" content="img/favicons/browserconfig.xml">
		<meta name="theme-color" content="#ffffff">

		<!-- important scripts to be loaded -->
		<script type="text/javascript" src="js/jquery-3.4.1.min.js"></script>
		<script type="text/javascript" src="js/bootstrap-4.4.1/bootstrap.bundle.min.js"></script>

		<!-- Bootstrap -->
		<link rel="stylesheet" type="text/css" href="css/bootstrap-4.4.1/bootstrap.min.css">
		<!-- Normalize -->
		<link rel="stylesheet" type="text/css" href="css/normalize-8.0.1.css">
		<!-- Font Awesome, all styles -->
		<link href="fonts/font-awesome-5.8.2/css/all.css" rel="stylesheet">

		<!-- include settings-style -->
		<link rel="stylesheet" type="text/css" href="settings/settings_style.css">

		<!-- clockpicker -->
		<script type="text/javascript" src="js/clockpicker/bootstrap-clockpicker.min.js"></script>
		<link rel="stylesheet" type="text/css" href="css/clockpicker/bootstrap-clockpicker.min.css">

	</head>

	<body>
		<?php

			/**
			 * read settings for input elements from config file
			 * and put them in associative array
			 */

			// some global vars
			$maxQuantityLp = 8;  // max configured lp
			$elemName = '';
			$elemId = '';
			$elemValue = '';
			$lp;
			$dayOfWeek;  // Mo = 1, ..., So = 7

			// first read config-lines in array
			$settingsFile = file('/var/www/html/openWB/openwb.conf');
			// prepare key/value array
			$settingsArray = [];

			// convert lines to key/value array for faster manipulation
			foreach($settingsFile as $line) {
				// split line at char '='
				$splitLine = explode('=', $line);
				// trim parts
				$splitLine[0] = trim($splitLine[0]);
				$splitLine[1] = trim($splitLine[1]);
				// push key/value pair to new array
				$settingsArray[$splitLine[0]] = $splitLine[1];
			}
			// now values can be accessed by $settingsArray[$key] = $value;

			$isConfiguredLp = array_fill(1, $maxQuantityLp, false); // holds boolean for configured lp
			// due to inconsitent variable naming need individual lines
			$isConfiguredLp[1] = true;  // lp1 always configured
			$isConfiguredLp[2] = ($settingsArray['lastmanagement'] == 1) ? 1 : 0;
			$isConfiguredLp[3] = ($settingsArray['lastmanagements2'] == 1) ? 1 : 0;
			for ($lp=4; $lp<=$maxQuantityLp; $lp++) {
				$isConfiguredLp[$lp] = ($settingsArray['lastmanagementlp'.$lp] == 1) ? 1 : 0;
			}

			// just to make sure... reset all elements for non-configured lp
			for ($lp=1; $lp<=$maxQuantityLp; $lp++) {
				if ( !$isConfiguredLp[$lp] ) {
					for ($dayOfWeek=1; $dayOfWeek<=7; $dayOfWeek++) {
						// all days...
						$settingsArray['lockBoxLp'.$lp.'_'.$dayOfWeek] = 'off';
						$settingsArray['lockTimeLp'.$lp.'_'.$dayOfWeek] = '';
						$settingsArray['unlockBoxLp'.$lp.'_'.$dayOfWeek] = 'off';
						$settingsArray['lockTimeLp'.$lp.'_'.$dayOfWeek] = '';
					}
				}
			}

			function getDayOfWeekString($dayOfWeek) {
				// returns name of the weekday
				switch ($dayOfWeek) {
					case 1:
						return 'Montag';
					case 2:
						return 'Dienstag';
					case 3:
						return 'Mittwoch';
					case 4:
						return 'Donnerstag';
					case 5:
						return 'Freitag';
					case 6:
						return 'Samstag';
					case 7:
						return 'Sonntag';
					default:
						return 'Wochentag?';
				}
			}

			function buildElementProperties($elemType) {
				// builds name, id and value strings for element
				global $lp, $dayOfWeek, $elemName, $elemId, $elemValue, $settingsArray;

				$elemId = $elemType.$lp.'_'.$dayOfWeek;
				$elemName = $elemType.'['.$lp.']['.$dayOfWeek.']';
				$elemValue = $settingsArray[$elemId];
			}

			function echoCheckboxDiv($elemType, $label) {
				// echoes the div to render checkbox to lock lp
				global $elemName, $elemId, $elemValue;
				buildElementProperties($elemType);
				// translate boolean to proper html
				if ( $elemValue == 'on' ) {
					$elemValue = " checked='checked'";
				} else {
					$elemValue = '';
				}
				echo <<<ECHOCHECKBOX
													<div class="col-auto my-1">
														<div class="form-check">
															<input type="hidden" name="{$elemName}">
															<input class="form-check-input" type="checkbox" id="{$elemId}" name="{$elemName}"{$elemValue}>
															<label class="form-check-label pl-10" for="{$elemId}">
																{$label}
															</label>
														</div>
													</div>

ECHOCHECKBOX;
			}

			function echoTimepickerDiv($elemType) {
				// echoes the div to render locktime timepicker for lp
				global $elemName, $elemId, $elemValue;
				buildElementProperties($elemType);
				echo <<<ECHOCLOCKPICKER
													<div class="col-sm-6 my-1">
														<div class="input-group">
															<input class="form-control" readonly id="{$elemId}" name="{$elemName}" placeholder="--" value="{$elemValue}">
															<div class="input-group-append">
																<span class="input-group-text far fa-xs fa-clock vaRow"></span>
															</div>
														</div>
													</div>\n
ECHOCLOCKPICKER;
			}

			function echoDayRow() {
				// echoes all elements for one day-row in form
				global $dayOfWeek;
				$dayOfWeekString = getDayOfWeekString($dayOfWeek);

				echo <<<ECHODAYROWHEAD
										<div class="row vaRow">  <!-- row {$dayOfWeekString} -->
											<div class="col-2">
									            {$dayOfWeekString}
									        </div>
											<div class="col-5">
												<div class="form-row align-items-center">\n
ECHODAYROWHEAD;

				echoCheckboxDiv('lockBoxLp', 'sperren');
				echoTimepickerDiv('lockTimeLp');

				echo <<<ECHODAYROWMIDDLE
												</div>
									        </div>
											<div class="col-5">
												<div class="form-row align-items-center">\n
ECHODAYROWMIDDLE;

				echoCheckboxDiv('unlockBoxLp', 'entsperren');
				echoTimepickerDiv('unlockTimeLp');

				echo <<<ECHODAYROWTAIL
												</div>
									        </div>
										</div>  <!-- end row {$dayOfWeekString} -->\n
ECHODAYROWTAIL;

				if ( $dayOfWeek < 7 ) {
					echo '						<hr class="d-sm-none">'."\n";
				}
			}  // end echoDayRow

		?>

<!-- begin of html body -->

		<?php include '/var/www/html/openWB/web/settings/navbar.html'; ?>

		<div role="main" class="container" style="margin-top:20px">
			<div class="row justify-content-center">

				<form class="form col-md-10" action="./tools/saveautolock.php" method="POST">

				<?php

					for ($lp=1; $lp<=$maxQuantityLp; $lp++) {
						// build form-groups for all lp
						if ( $isConfiguredLp[$lp] ) {
							// if lp is configured: display form-group
							$visibility = '';
						} else {
							// if lp is not configured: hide form-group
							$visibility = ' display: none;';
						}
						echo <<<ECHOFORMGROUPHEAD
							<div class="form-group px-3 pb-3" style="border:1px solid black;{$visibility}" id="lp{$lp}">  <!-- group charge point {$lp} -->
								<h1>LP {$lp} ({$settingsArray['lp'.$lp.'name']})</h1>\n
ECHOFORMGROUPHEAD;

						for ($dayOfWeek=1; $dayOfWeek<=7; $dayOfWeek++) {
								// build form-rows for all weekdays
								echoDayRow();
						}  // end all days

						echo <<<ECHOFORMGROUPTAIL
											<div class="row justify-content-center">
												<button type="button" class="btn btn-sm btn-red mt-2" onclick="resetLpData({$lp});">alles zurücksetzen</button>
											</div>

											</div>  <!-- end form-group charge point {$lp} -->
ECHOFORMGROUPTAIL;
					}  // end all lp

				?>

					<div class="row justify-content-center">
						<button type="submit" class="btn btn-lg btn-green">Einstellungen übernehmen</button>
					</div>

				</form>  <!-- end form -->
			</div>
			<br>


		</div>  <!-- end container -->

		<footer class="footer bg-dark text-light font-small">
	      <div class="container text-center">
			  <small>Sie befinden sich hier: Auto-Lock</small>
	      </div>
	    </footer>

		<script type="text/javascript">
			function addClockpicker(targetId) {
				// add a clockpicker to input targetId (eg #unlockTimeLp1_7)
				// and set input field to 00:00
				$(targetId).clockpicker({
					placement: 'bottom',  // clock popover placement
					align: 'left',  // popover arrow align
					donetext: '',  // done button text
					autoclose: true,  // auto close when minute is selected
					vibrate: true,  // vibrate the device when dragging clock hand
					default: "00:00"
				});
			}

			function removeClockpicker(targetId) {
				// remove a clockpicker in input targetId (eg #unlockTimeLp1_7)
				// and set input value to --
				if ( $(targetId).length ) {
					// if clockpicker exists
					$(targetId).clockpicker('remove');
					$(targetId).val('');
				}
			}

			 function resetLpData(chargePoint) {
				 for (day=1; day<=7; day++) {
					 // reset all days
					 $('#lockBoxLp'+chargePoint+'_'+day).prop('checked', false);
					 removeClockpicker('#lockTimeLp'+chargePoint+'_'+day);
					 $('#unlockBoxLp'+chargePoint+'_'+day).prop('checked', false);
					 removeClockpicker('#unlockTimeLp'+chargePoint+'_'+day);
				 }
			 }

			 $(document).ready(function(){

				 $(function() {
	 				// if a checkbox is checked/unchecked
	 				// add/remove respective clockpicker
	 				// and empty input field if removed
	 			    $('input:checkbox').change(function() {
	 					var boxIsChecked = $(this).prop('checked') == true;
	 					var clockPickerId = "#" + this.id.replace("Box", "Time");  // create matching clockpicker id
	 					if ( boxIsChecked ) {
	 						// activate clockpicker
							if ( $(clockPickerId).val() == '' ) {
								// replace empty field (placeholder = --) with initial time
								$(clockPickerId).val('00:00');
							}
	 						addClockpicker(clockPickerId);
	 					} else {
	 						// remove clockpicker
	 						removeClockpicker(clockPickerId);
	 					}
	 			    })
	 			 })

				 // initially add all clockpickers to visible form-groups
				 for (chargePoint=1; chargePoint<=8; chargePoint++) {
					 if ( $('#lp'+chargePoint).is(':visible') ) {
						 for (day=1; day<=7; day++) {
							 if ( $('#lockBoxLp'+chargePoint+'_'+day).prop('checked') == true ) {
								 addClockpicker('#lockTimeLp'+chargePoint+'_'+day)
							 }
							 if ( $('#unlockBoxLp'+chargePoint+'_'+day).prop('checked') == true ) {
								 addClockpicker('#unlockTimeLp'+chargePoint+'_'+day)
							 }
						 }
					 }
				 }
			});

	    </script>

	</body>
</html>