<?php 
session_start();
error_reporting(E_ALL|E_STRICT);
require_once('common/config.php');
require_once(CONFIG_PATH . 'common/mysql-handler.php');
//DBH->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Check to see if it someone is posting to our form
if(isset($_POST['action'])){

	// If it is a post, confirm that it is coming from our form.
	if(!isset($_SESSION['tokenized']) || $_SESSION['tokenized'] != $_POST['tokenized']){
		$errors = array("Invalid Session.  Please start over.");
	} else {
		if($_POST['action'] == "Continue to step 2"){
			
			// Step one, create the user and client, and tie them together.
			
			$sql = "INSERT INTO gem_users (email, created_at, updated_at) VALUES (:email, NOW(), NOW())";
			$q = $DBH->prepare($sql);
			$q->execute(array(
					':email' => $_POST['user']['email']
				)
			);
			
			$_POST['client']['email'] = $_POST['user']['email'];

			$q = $DBH->prepare("SELECT id FROM gem_users WHERE email = ? ORDER BY id DESC LIMIT 1");
			$q->setFetchMode(PDO::FETCH_ASSOC);
			$results = $q->execute(array($_POST['user']['email']));
			$ui = $q->fetchAll();
			$uid= $ui[0]['id'];
		
			$sql = "INSERT INTO gem_clients (user_id, first_name, last_name, home_phone, mobile_phone, contact_preference, created_at, updated_at) VALUES (:user_id, :first_name, :last_name, :home_phone, :mobile_phone, :contact_preference, NOW(), NOW())";
			$q = $DBH->prepare($sql);
			$q->execute(array(
					':user_id' => $uid,
					':first_name' => $_POST['client']['first_name'], 
					':last_name' => $_POST['client']['last_name'], 
					':home_phone' => $_POST['client']['home_phone'], 
					':mobile_phone' => $_POST['client']['mobile_phone'], 
					':contact_preference' => $_POST['client']['contact_preference']
				)
			);
			
			$q = $DBH->prepare("SELECT id FROM gem_clients WHERE user_id = ? ORDER BY id DESC LIMIT 1");
			$q->setFetchMode(PDO::FETCH_ASSOC);
			$results = $q->execute(array($uid));
			$ci = $q->fetchAll();
			$cid= $ci[0]['id'];
			
			$sql = "INSERT INTO gem_properties (client_id, interest, created_at, updated_at) VALUES (:client_id, :interest, NOW(), NOW())";
			$q = $DBH->prepare($sql);
			$q->execute(array(
					':client_id' => $cid,
					':interest' => $_POST['property']['interest']
				)
			);
			
			$q = $DBH->prepare("SELECT id FROM gem_properties WHERE client_id = ? ORDER BY id DESC LIMIT 1");
			$q->setFetchMode(PDO::FETCH_ASSOC);
			$results = $q->execute(array($cid));
			$tmp = $q->fetchAll();
			$_POST['property']['id'] = $tmp[0]['id'];
			
			$_POST['client']['client_id'] = $cid;
			$_SESSION['client'] = $_POST['client'];
			$_SESSION['property'] = $_POST['property'];
			
			$step = 'Property';
			
		} else if($_POST['action'] == 'Continue to step 3'){
			
			$_POST['property']['interest'] = $_SESSION['property']['interest'];
			$_POST['property']['id'] = $_SESSION['property']['id'];
				
			// Add the property to the database
			try {
				$sql = "UPDATE gem_properties SET address1=:address1, address2=:address2, city=:city, state=:state, zipcode=:zipcode, primary_residence=:primary_residence, updated_at=NOW() WHERE id=:property_id";
				$q = $DBH->prepare($sql);
				$q->execute(array(
						':address1' => $_POST['property']['address1'], 
						':address2' => $_POST['property']['address2'], 
						':city' => $_POST['property']['city'], 
						':state' => $_POST['property']['state'], 
						':zipcode' => $_POST['property']['zipcode'],
						':primary_residence' => $_POST['property']['primary_residence'],
						':property_id' => $_POST['property']['id']
					)
				);
			} catch (PDOException $e) {
				print "Error!: " . $e->getMessage() . "<br/>";
				die();
			}
			
			
//			 $q = $DBH->prepare("SELECT id FROM gem_properties WHERE client_id = ? ORDER BY id DESC LIMIT 1");
//			 $q->setFetchMode(PDO::FETCH_ASSOC);
//			 $results = $q->execute(array($_SESSION['client']['client_id']));
//			 $tmp = $q->fetchAll();
//			 $_POST['property']['id'] = $tmp[0]['id'];
			
			// Add the property to the database
			$sql = "INSERT INTO gem_basic_reports (property_id, sqft, average_yearly_utilities, year_built, created_at, updated_at) VALUES (:property_id, :sqft, :average_yearly_utilities, :year_built, NOW(), NOW())";
			$q = $DBH->prepare($sql);
			$q->execute(array(
					':property_id' => $_POST['property']['id'],
					':sqft' => filter_var($_POST['basic_report']['sqft'], FILTER_SANITIZE_NUMBER_FLOAT),
					':average_yearly_utilities' => filter_var($_POST['basic_report']['average_yearly_utilities'], FILTER_SANITIZE_NUMBER_FLOAT), 
					':year_built' => filter_var($_POST['basic_report']['year_built'], FILTER_SANITIZE_NUMBER_INT)
				)
			);
			
			$q = $DBH->prepare("SELECT id FROM gem_basic_reports WHERE property_id = ? ORDER BY id DESC LIMIT 1");
			$q->setFetchMode(PDO::FETCH_ASSOC);
			$results = $q->execute(array($_POST['property']['id']));
			$tmp = $q->fetchAll();
			$_POST['basic_report']['id'] = $tmp[0]['id'];
			
			$_SESSION['property'] = $_POST['property']; 
			$_SESSION['basic_report'] = $_POST['basic_report'];

			$step = 'Property Details';

		} else if ($_POST['action'] == 'Get Your Free Report'){

			$sql = "UPDATE gem_basic_reports SET level_of_efficiency = :level_of_efficiency, updated_at = NOW() WHERE id = {$_SESSION['basic_report']['id']}"; //,level_of_comfort = :level_of_comfort,level_of_comfort = :level_of_comfort,ease_of_process = :ease_of_process,budget = :budget,insulation = :insulation,hvac_system = :hvac_system,radiant_barrier = :radiant_barrier,lighting = :lighting,appliances = :appliances,solar_film_on_windows = :solar_film_on_windows,building_envelope_retrofit = :building_envelope_retrofit,windows = :windows,solar_hot_water = :solar_hot_water,roof_radiant = :roof_radiant,solar = :solar,rainwater_harvesting_system = :rainwater_harvesting_system,water_recycling = :water_recycling,
			$q = $DBH->prepare($sql); 
			$q->execute(array(
					':level_of_efficiency' => $_POST['basic_report']['level_of_efficiency'],
					/*':level_of_comfort' => $_POST['basic_report']['level_of_comfort'],
					':level_of_comfort' => $_POST['basic_report']['level_of_comfort'],
					':ease_of_process' => $_POST['basic_report']['ease_of_process'],
					':budget' => $_POST['basic_report']['budget'],
					':insulation' => $_POST['basic_report']['insulation'],
					':hvac_system' => $_POST['basic_report']['hvac_system'],
					':radiant_barrier' => $_POST['basic_report']['radiant_barrier'],
					':lighting' => $_POST['basic_report']['lighting'],
					':appliances' => $_POST['basic_report']['appliances'],
					':solar_film_on_windows' => $_POST['basic_report']['solar_film_on_windows'],
					':building_envelope_retrofit' => $_POST['basic_report']['building_envelope_retrofit'],
					':windows' => $_POST['basic_report']['windows'],
					':solar_hot_water' => $_POST['basic_report']['solar_hot_water'],
					':roof_radiant' => $_POST['basic_report']['roof_radiant'],
					':solar' => $_POST['basic_report']['solar'],
					':rainwater_harvesting_system' => $_POST['basic_report']['rainwater_harvesting_system'],
					':water_recycling' => $_POST['basic_report']['water_recycling']*/
				)
			);
			$_SESSION['basic_report_pt2'] = $_POST['basic_report'];
			$step = "Free Report";
		}
		
		header ("location: /forms/free-report/?step=$step");
	}
} else { 
 
	$token = md5(time() . rand(1,100));
	$_SESSION['tokenized'] = $token;
	
	// It isn't a post, so we need to decide what part of the process we are in.
	if(!isset($_GET['step'])){
		// This is the first page of the report.
/***********************
 *  BEGIN FORM STEP 1  *
 ***********************/
		$form = "<ul class='row steps'>
	<li class='col-1-3 current'><h5>Step 1</h5></li>
	<li class='col-1-3'><h5>Step 2</h5></li>
	<li class='col-1-3 last'><h5>Step 3</h5></li>
</ul>
		<h2>To get your Free Report, simply get started now on the questionnaire below!</h2>
		
			<form accept-charset='UTF-8' action='{$_SERVER['PHP_SELF']}' class='free-report' method='post' name='free-report'>
				<input type='hidden' name='tokenized' value='$token'/>
				<div class='field col-1-2'>
					<label for='client_first_name'>First name</label><input type='text' id='client_first_name' name='client[first_name]' required placeholder=''>
				</div>
				<div class='field col-1-2'>
					<label for='client_last_name'>Last name</label><input type='text' id='client_last_name' name='client[last_name]' required placeholder=''>
				</div>
				<div class='field col-1'>
					<label for='client_email'>Email</label><input type='email' id='client_email' name='user[email]' required placeholder=''>
				</div>
				<div class='field col-1-2 phone'>
					<label for='client_mobile_phone'>Mobile phone</label><input type='tel' id='client_mobile_phone' name='client[mobile_phone]' placeholder=''>
				</div>
				<div class='field col-1-2 phone'>
					<label for='client_home_phone'>Home phone</label><input type='tel' id='client_home_phone' name='client[home_phone]' placeholder=''>
				</div>
				<div class='field col-1'>
					<label for='client_contact_preference'>Contact preference</label><select id='client_contact_preference' name='client[contact_preference]'>
						<option value='Email'>Email</option>
						<option value='Phone'>Phone</option>
					</select>
				</div> 

                                <div class='field col-1'>
					<label for='property_interest'>property_interest</label><select id='property_interest' name='property[interest]'>
						<option value='old-retrofit'>old-retrofit</option>
						<option value='tear-down'>tear-down</option>
						<option value='new-retrofit'>new-retrofit</option>
						<option value='new-construction'>new-construction</option>
					</select>
				</div>

				<!--input id='property_interest' name='property[interest]' value='old-retrofit' type='hidden'-->
				
				<div class='actions'>
					<input name='action' type='submit' value='Continue to step 2'>
				</div>
			</form>";

/*********************************
 *  BEGIN FORM STEP 1 - SIDEBAR  *
 *********************************/		
		$sidebar = "<h2>Learn How Much Energy Upgrades Could Increase Your Home Value.</h2>
		<p>It's easy and takes just a few minutes. Simply answer the step-by-step questionnaire. The Free Report will provide:<p>

•	An estimated range of energy upgrade costs, based on level of energy reduction you choose from our menu.  Costs vary depending on condition, regional building and energy costs, and other factors.<p>
•	An estimate of your home's estimated “green premium” value once your upgrades are completed.<p> 
•	Potential estimated early payoff on loan – assuming energy savings applied to loan balance.<p>

This information supports important decisions about whether--and a range of how much--to invest in energy upgrades for your existing property.  <p>Once you have determined estimated costs, and potential return on investment it’s easy to proceed to obtain loan approval and be connected to our preferred builders/contractors to receive bids, energy audits and facilitate the necessary process.   <p> 

If you aren’t sure what your utility costs are, you may want to have access to the last year's utility bills handy, as one of the questions relates to yearly utility costs.  Please include all utility costs including electric, gas, propane, oil, etc.  
</p>";
/*******************************
 *  END FORM STEP 1 - SIDEBAR  *
 *******************************/

/*********************
 *  END FORM STEP 1  *
 *********************/
	} else {
		if($_GET['step'] == 'Property'){
/**********************************
 *  BEGIN FORM STEP 2 - PROPERTY  *
 **********************************/	
			$form = "<ul class='row steps'>
	<li class='col-1-3'><h5>Step 1</h5></li>
	<li class='col-1-3 current'><h5>Step 2</h5></li>
	<li class='col-1-3 last'><h5>Step 3</h5></li>
</ul>
				<form accept-charset='UTF-8' action='{$_SERVER['PHP_SELF']}' class='free-report new_property' id='new_property' method='post' name='new_property'><input type='hidden' name='tokenized' value='$token'/>";
			$interest = $_SESSION['property']['interest'];
			if($interest == "old-retrofit" || $interest == "tear-down"){ // upgrade your home
/*************************************************
 *  BEGIN FORM STEP 2[OLD RETROFIT OR TEARDOWN]  *
 *************************************************/	
			$form .= "<fieldset>
	<div class='field col-1'>
		<legend><h4>What property are you interested in upgrading?</h4></legend>
	</div>
	<div class='field col-1 address'>
		<label for='property_address1'>Address</label><input id='property_address1' name='property[address1]' required placeholder='' type='text'>
	</div>
	<div class='field col-1 address'>
		<label for='property_address2'>Unit/Apt.</label><input id='property_address2' name='property[address2]' placeholder='' type='text' />
	</div>
	<div class='field city'>
		<label for='property_city'>City</label><input id='property_city' name='property[city]' required='' placeholder='' type='text' />
	</div>
	<div class='field state'>
		<label for='property_state'>State</label><input id='property_state' name='property[state]' required='' placeholder='' type='text' />
	</div>
	<div class='field zip'>
		<label for='property_zipcode'>Zipcode</label><input id='property_zipcode' name='property[zipcode]' placeholder='' type='text' />
	</div>
	<div class='field'>
		<label for='property_primary_residence'>Primary residence</label><select name='property[primary_residence]'><option value='yes'>yes</option><option value='no'>no</option></select>
	</div>
</fieldset>
<p></p>";
/***********************************************
 *  END FORM STEP 2[OLD RETROFIT OR TEARDOWN]  *
 ***********************************************/		
			} else if($interest == "new-retrofit" || $interest == "new-construction"){ // buy and upgrade a home
/*********************************************************
 *  BEGIN FORM STEP 2[NEW RETROFIT OR NEW CONSTRUCTION]  *
 *********************************************************/
				$form .= "<fieldset>
	<div class='field col-1'>
		<legend></h4>Where are you looking for a property?</h4></legend>
	</div>
	<div class='field city'>
		<label for='property_city'>City</label><input id='property_city' name='property[city]' required='' placeholder='' type='text' />
	</div>
	<div class='field state'>
		<label for='property_state'>State</label><input id='property_state' name='property[state]' required='' placeholder='' type='text' />
	</div>
	<div class='field zip'>
		<label for='property_zipcode'>Zipcode</label><input id='property_zipcode' name='property[zipcode]' placeholder='' type='text' />
	</div>
	<div class='field'>
		<label for='property_price_range'>What is your price range?</label><input id='property_price_range' name='property[price_range]' type='text' placeholder='$120,000-$250,000'>
	</div>
</fieldset>
<p></p>";
/*******************************************************
 *  END FORM STEP 2[NEW RETROFIT OR NEW CONSTRUCTION]  *
 *******************************************************/
			} 
			
			if($interest == "old-retrofit") {
				// Gather the property Details
/*************************************
 *  BEGIN FORM STEP 2[OLD RETROFIT]  *
 *************************************/
				$form .= "<fieldset>
	<div class='field col-1'>
		<legend>Property Details:</legend> 
	</div>
	<div class='field col-1 details'>
		<label for='basic_report_year_built'>What year was the property built?</label><input id='basic_report_year_built' name='basic_report[year_built]' placeholder='If unknown, use best estimated year' type='text'>
	</div>
	<div class='field col-1 details'>
		<label for='basic_report_sqft'>What is the interior square footage of the property?</label><input id='basic_report_sqft' name='basic_report[sqft]' placeholder='If unknown, use best estimate' type='text'>
	</div>
	<!--div class='field'>
		<label for='basic_report_number_of_windows'>How many windows does the property have?</label><input id='basic_report_number_of_windows' name='basic_report[number_of_windows]' placeholder='15' type='number'>
	</div-->
	<div class='field col-1 details'>
		<label for='basic_report_average_yearly_utilities'>What are the average yearly utilities (include electric, gas, propane, oil, etc. yearly costs)?</label><input id='basic_report_average_yearly_utilities' name='basic_report[average_yearly_utilities]' placeholder='If unknown, use best estimate' type='text'>
	</div>
	<!--div class='field'>
		<label for='basic_report_years_owned'>How many years have you owned the property?</label><input id='basic_report_years_owned' name='basic_report[years_owned]' placeholder='25' type='text'>
	</div-->
</fieldset>
<p></p>";
/***********************************
 *  END FORM STEP 2[OLD RETROFIT]  *
 ***********************************/
			}

			// Gather the property Details
			$form .= "
					<div class='actions'>
						<input name='action' type='submit' value='Continue to step 3'>
					</div>
				</form>";
			
			$client = $_SESSION['client'];
			// End Property
/********************************
 *  END FORM STEP 2 - PROPERTY  *
 ********************************/				
		} else if($_GET['step'] == 'Property Details') {
/******************************************
 *  BEGIN FORM STEP 3 - PROPERTY DETAILS  *
 ******************************************/	
			$form = "<h4>The more information you can give us, the more accurate of a report we can generate for you.</h4>
			<script>
				Number.prototype.formatMoney = function(decPlaces, thouSeparator, decSeparator) {
				    var n = this,
				    decPlaces = isNaN(decPlaces = Math.abs(decPlaces)) ? 2 : decPlaces,
				    decSeparator = decSeparator == undefined ? '.' : decSeparator,
				    thouSeparator = thouSeparator == undefined ? ',' : thouSeparator,
				    sign = n < 0 ? '-' : '',
				    i = parseInt(n = Math.abs(+n || 0).toFixed(decPlaces)) + '',
				    j = (j = i.length) > 3 ? j % 3 : 0;
				    return sign + (j ? i.substr(0, j) + thouSeparator : '') + i.substr(j).replace(/(\d{3})(?=\d)/g, '$1' + thouSeparator) + (decPlaces ? decSeparator + Math.abs(n - i).toFixed(decPlaces).slice(2) : '');
				};
				(function($){
					$(function(){
						var cost = {
							\"E-4\":[8,12],
							\"E-3\":[12,20],
							\"E-2\":[20,25],
							\"E-1\":[25,50]
						}
						function calculatePrice(){
							var sqft = {$_SESSION['basic_report']['sqft']};
							var hers = $('#basic_report_level_of_efficiency').val();
							return((sqft*cost[hers][0]).formatMoney(0,',','.') + ' - ' + (sqft*cost[hers][1]).formatMoney(0,',','.'));
						}
						$('#budget').text(calculatePrice());
						$('#basic_report_level_of_efficiency').change(function(e){
							$('#budget').text(calculatePrice());
						});
					})
				})(jQuery)
			</script>
			
				<form accept-charset='UTF-8' action='{$_SERVER['PHP_SELF']}' class='free-report' id='free_report' method='post' name='free_report'>
					<input type='hidden' name='tokenized' value='$token'/>
					<div style='margin:0;padding:0;display:inline'>
						<input name='utf8' type='hidden' value='✓'>
					</div>
					<fieldset>
						<legend><h4>Current state of the property</h4></legend>
						<!--div class='field'>
							<label for='basic_report_current_upgrades'>Have you already upgraded this property?  If so can you tell us what you've done?</label><br>
							<textarea id='basic_report_current_upgrades' name='basic_report[current_upgrades]' placeholder='Can you give us deails?'></textarea>
						</div-->
						<div class='radioset'>
							<label for='basic_report_level_of_efficiency'>What are field energy efficiency - reduction goals and, if applicable, energy production goals? Keep in mind, If you're planning to install renewable systems, your property should be very efficient before energy production is financially viable.</label>
							<select id='basic_report_level_of_efficiency' name='basic_report[level_of_efficiency]'>
								<option value='E-4'>Up to 25% - Weatherization.</option>
								<option value='E-3'>Up to 50% - Retrofit or Energy Efficient Construction.</option>
								<option value='E-2'>Up to 75% - Deep Retrofit or High Performance Building.</option>
								<option value='E-1'>Up to 100% or greater - to include Renewable Energy Power Generation.</option>
							</select>
						</div>
					<!-- INSERT FIELDSET2 -->
					</fieldset>
					<!-- INSERT FIELDSET1 -->
					<div class='actions'>
						<input name='action' type='submit' value='Get Your Free Report'>
					</div>
				</form>";
			
			$client = $_SESSION['client'];
			$property = $_SESSION['property'];
			$basic_report = $_SESSION['basic_report'];
			
			// End Property Details
/****************************************
 *  END FORM STEP 3 - PROPERTY DETAILS  *
 ****************************************/			
		} else if($_GET['step'] == 'Free Report'){
/******************************
 *  BEGIN FREE REPORT OUTPUT  *
 ******************************/
				
				$report = "<h1>Your Free Report</h1>";

				$client = $_SESSION['client'];
				$property = $_SESSION['property'];
				$basic_report = $_SESSION['basic_report'];
				$basic_report_pt2 = $_SESSION['basic_report_pt2'];

		$cost = array(
					"E-4" => array('8','12'),
					"E-3" => array('12','20'),
					"E-2" => array('20','25'),
					"E-1" => array('25','50')
				);
				
		$saving = array(
					"E-4" => array('0.1','0.25'),
					"E-3" => array('0.25','0.4'),
					"E-2" => array('0.4','0.6'),
					"E-1" => array('0.6','0.85')
				);
//$property_median_budget_lo	= "5000";//$cost[$basic_report_pt2['level_of_efficiency']][0] * (int)$basic_report['sqft'];//,$cost[$basic_report_pt2['level_of_efficiency']][1] * (int)$basic_report['sqft']];	
//$property_median_budget_hi	= "10000";//$cost[$basic_report_pt2['level_of_efficiency']][1] * (int)$basic_report['sqft'];//,$cost[$basic_report_pt2['level_of_efficiency']][1] * (int)$basic_report['sqft']);
				$eff=$basic_report_pt2['level_of_efficiency'];
				$property_median_budget_lo= $cost[$eff][0] * (int)$basic_report['sqft'];
				$property_median_budget_hi= $cost[$eff][1] * (int)$basic_report['sqft'];
				$property_median_budget = array($property_median_budget_lo, $property_median_budget_hi);
/*
				echo $eff;
				echo $property_median_budget_lo;
				echo $property_median_budget_hi;
				echo $property_median_budget[0];
				echo $cost[$eff][1];
				echo (int)$basic_report['sqft'];
*/
				
				switch($basic_report_pt2['level_of_efficiency']){
					case "E-4":
						$property_renew_type = "Weatherization";
						break;
					case "E-3":
						$property_renew_type = "Light Retrofit";
						break;
					case "E-2":
						$property_renew_type = "Deep Retrofit";
						break;
					case "E-1":
						$property_renew_type = "Net Zero";
						break;
					default:
						$property_renew_type = "Unknown";
				}

				$loan_int_rate = 4.5;  //interest rate
				$years = 20; //loan term
				$es_m = 100; //Default energy_savings payment per month
				
				if(isset($basic_report['average_yearly_utilities']) && $basic_report['average_yearly_utilities'] > 0){
					$es_m = $basic_report['average_yearly_utilities'] / 12;
				}
				
				$term = $years * 12;
				$term_m = $term * 12;

				$rate = $loan_int_rate / 100;  //rate in percentage
				$rate_m = $rate / 12;          //monthly rate in percentage
				$es = $es_m * 12;          //yearly energy payment
				$pv = $es_m * (1 - pow(1 + $rate_m, -$term_m)) / $rate_m;

				$der  = 15 * $es;

				//$ipv_lo = ($der + $pv) / 2 * $saving[$basic_report_pt2['level_of_efficiency']][0];
				$ipv_lo = $property_median_budget[0] * .78;
				//$ipv_hi = ($der + $pv) / 2 * $saving[$basic_report_pt2['level_of_efficiency']][1];
				$ipv_hi = $property_median_budget[1] * .78;
				/*
				
					$zero = 0;	 
					$cpmts = $years * 12;               //number of payments (12 per year)
					$npmts = $years * 24;              //number of payments (24 per year)
					$spmts = $years * 26;               //number of payments (26 per year)

					$erate = $loan_int_rate / 100;   //to get actual interest in decimal format, ie 5% = .05

					$mrate = $erate / 24; //bi-weekly interest rate
					$srate = $erate / 26; //accelerated bi-weekly interest rate
					$crate = $erate / 12;  //conventional monthly interest rate
					$apr_amount = $amount * 1.04755;
					$pmt = $amount * ($mrate / (1 - pow(1 + $mrate,-$npmts))); //bi-weekly payment
					$epmt = $pmt + ($es_m * 12) / 26;  // energy savings bi-weekly payment + regular bi-weekly payment
					$cpmt = $amount * ($crate / (1 - pow(1 + $crate, -$cpmts))); // conventional monthly payment
					$apr_pmt = $apr_amount * ($crate / (1 - pow(1 + $crate, -$cpmts)));
					$bnpmts= -(log(1 - ($amount / $pmt) * ($srate))) / (log(1 + ($srate))); // number of payments without energy savings applied
					$enpmts= -(log(1 - ($amount / $epmt) * ($srate))) / (log(1 + ($srate))); // number of payments with energy savings applied
					$cnpmts= -(log(1 - ($amount / $cpmt) * ($crate))) / (log(1 + ($crate))); // number of conventional monthly payments without energy savings applied

					$tpmt = $pmt * round($bnpmts,2);     // total amount paid at end of loan
					$etpmt = $epmt * round($enpmts,2);       // total amount paid at end of loan with energy savings applied
					$ctpmt = $cpmt * round($cnpmts,2);   // total amount paid at end of conventional monthly loan

					$tint = $tpmt - $amount;           // total amount of interest paid at end of loan
					$etint = $etpmt - $amount;        // total amount of interest paid at end of loan with energy savings applied
					$ctint = $ctpmt - $amount;         // total amount of interest paid at end of conventional monthly loan

					$esapmt = ($es_m * 12) / 26;
					$tesa = $esapmt * $enpmts;         // total energy savings applied

					$int_saved = $ctint - $tint;    // interest saved w / o energy payments
					$eint_saved = $ctint - $etint;    // interest saved w/ energy payments
					$cint_saved = $ctint - $etint; 		// -$tint;   // conventional total monthly interest minus bi - weeky total interest
					$term = $years * 12; 
					$nterm = $bnpmts / 26;              // loan term in years, no extra payment
					$eterm = $enpmts / 26;            // loan term in years, with extra payments ($es_m)
					$cterm = $cnpmts / 12;
					$epmt2 = $epmt * 2;

				// $pc = $upg_total_cost;  //project cost // Lava flow
				$term_m = $term * 12;
				$es_term = $es_m * $term_m;    //total energy payments over loan
				$es_a = $es_term / 20;
				$rate = $loan_int_rate / 100;  //rate in percentage
				$rate_m = $rate / 12;          //monthly rate in percentage
				$es = $es_m * 12;          //yearly energy payment             //
				$pv = $es_m * (1 - pow(1 + $rate_m, -$term_m)) / $rate_m;
				$fv = $es_m * (pow(1 + $rate_m, $term_m) -1) / $rate_m;
				$cr = $es / $rate;
				$der_lo  = 10 * $es;
				$der_hi = 15 * $es;  
				$ipv_lo = (($der_lo + $pv) / 2);
				$ipv_hi = (($der_hi + $pv) / 2);
				$ipv_lo_lo= $ipv_lo * .65;
				$ipv_hi_lo= $ipv_hi * .65;
				// $upg_prop_value= $prop_value - $ipv; // Lava Flow
				$upg_property_value_increase= $ipv + $prop_value;
				$equity_savings_lo= $ipv_lo_lo + $int_saved;
				$equity_savings_hi= $ipv_hi + $int_saved;
				$eequity_savings_lo= $ipv_lo + $eint_saved;
				$eequity_savings_hi= $ipv_hi + $eint_saved;
				$value_increase_lo= $upg_property_value_increase;
				$value_increase_hi= $upg_property_value;
				$pmt_month_lo= $pmt;
				$pmt_month_hi= $epmt;
				$int_savings_lo= $int_saved;
				$int_savings_hi=  $eint_saved;
				$payback_lo= $nterm;
				*/
				
				function payback_rate($amount, $es_m){
					global $years;
					global $loan_int_rate;
					
					$erate = $loan_int_rate / 100;   //to get actual interest in decimal format, ie 5% = .05
					$mrate = $erate / 24; // bi-weekly interest rate
					$srate = $erate / 26; // accelerated bi-weekly interest rate
					$npmts = $years * 24;
					
					$pmt = $amount * ($mrate / (1 - pow(1 + $mrate,-$npmts))); //bi-weekly payment
					$epmt = $pmt + ($es_m * 12) / 26;  // energy savings bi-weekly payment + regular bi-weekly payment
					$enpmts= -(log(1 - ($amount / $epmt) * ($srate))) / (log(1 + ($srate))); // number of payments with energy savings applied
					return($enpmts / 26);
				}

				$payback_hi = payback_rate($property_median_budget[1], $es_m*$saving[$basic_report_pt2['level_of_efficiency']][0]);
				$payback_low = payback_rate($property_median_budget[0], $es_m*$saving[$basic_report_pt2['level_of_efficiency']][0]);
				
				$report = "
					<h1 class='report-title'>Free GEM Retrofit Estimation</h1>
				<div class='entry-content'>
					<span id='dated'>Compiled on " . date('M d, Y',time()) . "</span>
					<img src='http://www.greenenergy-money.com/free-report/images/eco_house_graphics/cutouts/house_view.jpg' width='300' height='272' alt='House View' class='alignleft'>
					<div class='primary-attributes'>

						<span class='tooltip tooltip-effect-1'>
							<span class='tooltip-item'> 
								<span class='label'>Energy Efficiency Level</span>
								<span class='data'>" . $basic_report_pt2['level_of_efficiency'] . "</span> 
							</span> 
							<span class='tooltip-content clearfix'>  
								<span class='tooltip-text'>
									This is the potential rating level of the energy efficiency and future building performance. Range from E1+ (best) through E5 (lowest).
								</span>
							</span>
						</span>

						<span class='tooltip tooltip-effect-1'>
							<span class='tooltip-item'>
								<span class='label'>Type of Energy Improvement</span>
								<span class='data'>" . $property_renew_type . "</span> 
							</span> 
							<span class='tooltip-content clearfix'>  
								<span class='tooltip-text'> 
									The stage of upgrade depends on budget, types of building insulation and renewable energy measures - Weatherization, Light Retrofit, Deep Retrofit, Net Zero, &amp; Net Zero Plus.
								</span>
							</span>
						</span>
						
						<span class='tooltip tooltip-effect-1'>
							<span class='tooltip-item'>
								<span class='label'>Upgrade Budget Range*</span>
								<span class='data'>\$" . number_format($property_median_budget[0], 2) . " - $" . number_format($property_median_budget[1], 2) . "</span>
							</span>
							<span class='tooltip-content clearfix'>   
								<span class='tooltip-text'> 
									The stage of upgrade depends on budget, types of building insulation and renewable energy measures - Weatherization, Light Retrofit, Deep Retrofit, Net Zero, &amp; Net Zero Plus.
								</span>
							</span>
						</span>

						<span class='tooltip tooltip-effect-1'>
							<span class='tooltip-item'>
								<span class='label'>Estimated increase in property value</span>
								<span class='data'>\$" . number_format($ipv_lo, 2) . "  -  \$" . number_format($ipv_hi, 2) . "</span>
							</span>
							<span class='tooltip-content clearfix'>  
								<span class='tooltip-text'> 
									Estimated value of property after improvements are completed, calculated using range of utility cost savings at Present Value of 20 years.
								</span>
							</span>
						</span>

						
					</div>
					<div class='salespitch'>
						<span class='tooltip tooltip-effect-1'>
							<span class='tooltip-item'>
								<span class='label'>Pay off your improvement cost (20 years loan assumed) in approximately</span>
								<span class='data' style='margin-top:8px;margin-bottom:8px'>" .  number_format($payback_low, 1)  . " - " .  number_format($payback_hi, 1)  . " Years</span>When you apply your energy savings to the loan
							</span>
							<span class='tooltip-content wide clearfix'>  
								<span class='tooltip-text'> 
									With GEM Methods, you can reduce the length of your loan by using savings from your utility bills!
								</span>
							</span>
						</span>				
					</div>

					";
					/* <span class='tooltip' data-tooltip=''>Without Energy Savings Applied</span>
						<!--span class='tooltip' data-tooltip=''>Interest Cost Savings : $" .  number_format($int_savings_lo, 2)  . "</span><br />
						<span class='tooltip' data-tooltip='Your estimated property value increase (IPV) and loan interest savings with GEM's Methods. (For more detailed info go to GEM’s free calculators or buy the Book &amp; Paid Report)'>Total Equity + Savings : $" .  number_format($equity_savings_lo, 2)  . "  to  $" .   number_format($equity_savings_hi, 2)  . "</span><br /-->
						<span class='tooltip' data-tooltip='With GEM Methods, you can reduce the length of your loan by using savings from your utility bills!'><span class='label'>Payoff  (20 year term)</span> <span class='data'>" .  number_format($payback_lo, 1)  . " Years</span></span> */ 
			/*$report .="					<span class='tooltip center' data-tooltip='Amount of cost–to-property value'>Cost-to-Value Percentage : <span class='data'>" .  number_format(100*$ipv_lo/$property_median_budget[1], 1) . "% to  " .  number_format(100*$ipv_hi/$property_median_budget[0], 1)  . "%</span> </span> */

					$report .= "<p class='cleared'><em>Subject to inspection, contractor recommendations, pricing, final loan, interest rate approval. Estimated costs vary on property condition, age, region, occupancy, and other factors. This is not a formal proposal. Rates and costs subject to market conditions.</em></p>
					<p style='margin-top: 30px;'><strong>IMPORTANT! Make sure to document past utility costs and get a qualified HERS audit to obtain a valuation that a lender will accept.</strong></p>
					<p><em>The estimated costs do not include any available Federal, State or Local tax credits or rebates associated with renewable upgrades that could reduce the capital cost outlay.</em></p>
				</div>";
				$report .= "<h3 style='text-align:center'><a href=http://tlopez.westloan.com/>Get pre-approved for financing</a> or <a href='http://www.greenenergy-money.com/contact-us/'>contact us</a> to speak to a representative.</h3>
					<p style='text-align:center'>-Rates are subject to change-<br>-This estimated report does not constitute loan, contractor bid approval or final appraisal valuation-</p>";
		}
		
		
		$sidebar = '';
		if(isset($form) && strlen($form) > 0){
			if(isset($client)){
				$sidebar .= "<h2>Your Details:</h2>
				<p>{$client['first_name']} {$client['last_name']}</p>
				<p>{$client['email']}</p>";
				if(strlen($client['home_phone']) > 0){
					$sidebar .= "<p>h: {$client['home_phone']}</p>";
				}
				if(strlen($client['mobile_phone']) > 0){
					$sidebar .= "<p>m: {$client['mobile_phone']}</p>";
				}
			}
			if(isset($property)){
				$sidebar .= "<h2>Property:</h2>
				<p>{$property['address1']}";
				if(strlen($property['address2']) > 0){
					$sidebar .= " {$property['address2']}";
				}
				$sidebar .= "<br>
				{$property['city']}, {$property['state']} {$property['zipcode']}</p>";
			}
			if(isset($basic_report)){
				$temp = '';
				if(strlen($basic_report['sqft']) > 0){
					$temp .= "<p>Square Footage: {$basic_report['sqft']}";
				}
				if(strlen($basic_report['average_yearly_utilities']) > 0){
					$temp .= "<p>Avg. Yearly Utilities: {$basic_report['average_yearly_utilities']}";
				}
				if(strlen($basic_report['year_built']) > 0){
					$temp .= "<p>Year Built: {$basic_report['year_built']}";
				}
				if(strlen($temp) > 0 ){
					$sidebar .= "<h2>Report Details:</h2>" . $temp . "<br><br><strong>What You Should Know:</strong><br>Typically most property upgrades don’t always realize an exact dollar-for-dollar cost on an appraisal valuation.  As an example, a new kitchen upgrade (average cost in the US is $50k) doesn’t usually appraise at cost.  The same came be said for other upgrades like swimming pools.  GEM chose to take a conservative approach in the incremental property value estimate – final value will depend on many factors and appraiser’s determination, and in some cases the owner may realize a full investment of costs and value.  The GEM beta appraisal program (tested in several U.S. regions) has realized an average of 78% of energy upgrade costs on appraisal valuations."  ;
				}
			}
		}
	} 
}
/****************************
 *  END FREE REPORT OUTPUT  *
 ****************************/
?><!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en-US" prefix="og: http://ogp.me/ns#"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8" lang="en-US" prefix="og: http://ogp.me/ns#"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9" lang="en-US" prefix="og: http://ogp.me/ns#"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en-US" prefix="og: http://ogp.me/ns#"> <!--<![endif]-->

<?php 
/*********************
 *  BEGIN HTML HEAD  *
 *********************/
?>
<head>
  <meta name="p:domain_verify" content="8700c9298f013ac11702c6ddf5b31137"/>
  <meta charset="utf-8">
    <title>The Free Report - Green Energy Money</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
<script type='text/javascript' src='main.js?ver=1.0.0'></script>    



		<script type='text/javascript' src='http://www.greenenergy-money.com/wp-includes/js/jquery/jquery.js?ver=1.10.2'></script>
		<script type='text/javascript' src='http://www.greenenergy-money.com/wp-content/themes/responsive/core/js/responsive-modernizr.js?ver=2.6.1'></script>
		<script type='text/javascript' src='http://www.greenenergy-money.com/wp-includes/js/jquery/ui/jquery.ui.core.min.js?ver=1.10.3'></script>
		<script type='text/javascript' src='http://www.greenenergy-money.com/wp-includes/js/jquery/ui/jquery.ui.widget.min.js?ver=1.10.3'></script>
		<script type='text/javascript' src='http://www.greenenergy-money.com/wp-includes/js/jquery/ui/jquery.ui.button.min.js?ver=1.10.3'></script>
		<script type='text/javascript' src='http://www.greenenergy-money.com/wp-includes/js/jquery/ui/jquery.ui.mouse.min.js?ver=1.10.3'></script>
		<script type='text/javascript' src='http://www.greenenergy-money.com/wp-includes/js/jquery/ui/jquery.ui.resizable.min.js?ver=1.10.3'></script>
		<script type='text/javascript' src='http://www.greenenergy-money.com/wp-includes/js/jquery/ui/jquery.ui.draggable.min.js?ver=1.10.3'></script>
		<script type='text/javascript' src='http://www.greenenergy-money.com/wp-includes/js/jquery/ui/jquery.ui.position.min.js?ver=1.10.3'></script>
		<script type='text/javascript' src='http://www.greenenergy-money.com/wp-includes/js/jquery/ui/jquery.ui.dialog.min.js?ver=1.10.3'></script>
		<script type='text/javascript' src='main.js?ver=1.0.0'></script>



  
<!-- This site is optimized with the Yoast WordPress SEO plugin v2.1.1 - https://yoast.com/wordpress/plugins/seo/ -->
<!-- Admin only notice: this page doesn't show a meta description because it doesn't have one, either write it for this page specifically or go into the SEO -> Titles menu and set up a template. -->
<link rel="canonical" href="http://www.greenenergy-money.com/?page_id=1100" />
<meta property="og:locale" content="en_US" />
<meta property="og:type" content="article" />
<meta property="og:title" content="The Free Report - Green Energy Money" />
<meta property="og:description" content="Step 1 Step 2 Step 3 To get your Free Report, simply get started now on the questionnaire below! First name Last name Email Mobile phone Home phone Contact preferenceEmailPhone" />
<meta property="og:url" content="http://www.greenenergy-money.com/?page_id=1100" />
<meta property="og:site_name" content="Green Energy Money" />
<meta name="twitter:card" content="summary"/>
<meta name="twitter:description" content="Step 1 Step 2 Step 3 To get your Free Report, simply get started now on the questionnaire below! First name Last name Email Mobile phone Home phone Contact preferenceEmailPhone"/>
<meta name="twitter:title" content="The Free Report - Green Energy Money"/>
<meta name="twitter:domain" content="Green Energy Money"/>
<script type='application/ld+json'>{"@context":"http:\/\/schema.org","@type":"WebSite","url":"http:\/\/www.greenenergy-money.com\/","name":"Green Energy Money"}</script>
<!-- / Yoast WordPress SEO plugin. -->

<link rel="alternate" type="application/rss+xml" title="Green Energy Money &raquo; Feed" href="http://www.greenenergy-money.com/feed/" />
<link rel="alternate" type="application/rss+xml" title="Green Energy Money &raquo; Comments Feed" href="http://www.greenenergy-money.com/comments/feed/" />
<link rel="alternate" type="application/rss+xml" title="Green Energy Money &raquo; The Free Report Comments Feed" href="http://www.greenenergy-money.com/?page_id=1100/feed/" />
<link rel='stylesheet' id='jquery.bxslider-css'  href='http://www.greenenergy-money.com/wp-content/plugins/bxslider-integration/assets/css/bxslider-integration.min.css?ver=4.1.4' type='text/css' media='all' />
<link rel='stylesheet' id='open-sans-css'  href='//fonts.googleapis.com/css?family=Open+Sans%3A300italic%2C400italic%2C600italic%2C300%2C400%2C600&#038;subset=latin%2Clatin-ext&#038;ver=4.1.4' type='text/css' media='all' />
<link rel='stylesheet' id='boxes-css'  href='http://www.greenenergy-money.com/wp-content/plugins/wordpress-seo/css/adminbar.min.css?ver=2.1.1' type='text/css' media='all' />
<link rel='stylesheet' id='parent-style-css'  href='http://www.greenenergy-money.com/wp-content/themes/virtue/style.css?ver=4.1.4' type='text/css' media='all' />
<link rel='stylesheet' id='dd_custom_style-css'  href='http://www.greenenergy-money.com/wp-content/themes/GEM%20Child/dd_style.css?ver=4.1.4' type='text/css' media='all' />
<link rel='stylesheet' id='kadence_theme-css'  href='http://www.greenenergy-money.com/wp-content/themes/virtue/assets/css/virtue.css?ver=246' type='text/css' media='all' />
<link rel='stylesheet' id='virtue_skin-css'  href='http://www.greenenergy-money.com/wp-content/themes/virtue/assets/css/skins/default.css' type='text/css' media='all' />
<link rel='stylesheet' id='roots_child-css'  href='http://www.greenenergy-money.com/wp-content/themes/GEM%20Child/style.css' type='text/css' media='all' />
<link rel='stylesheet' id='redux-google-fonts-virtue-css'  href='http://fonts.googleapis.com/css?family=Lato%3A400%2C700&#038;ver=1430347984' type='text/css' media='all' />
<script type='text/javascript' src='http://www.greenenergy-money.com/wp-includes/js/jquery/jquery.js?ver=1.11.1'></script>
<script type='text/javascript' src='http://www.greenenergy-money.com/wp-includes/js/jquery/jquery-migrate.min.js?ver=1.2.1'></script>
<script type='text/javascript' src='http://www.greenenergy-money.com/wp-content/plugins/bxslider-integration/assets/js/bxslider-integration.min.js?ver=4.1.4'></script>
<script type='text/javascript' src='http://www.greenenergy-money.com/wp-content/plugins/wp-google-analytics/wp-google-analytics.js?ver=0.0.3'></script>
<script type='text/javascript' src='http://www.greenenergy-money.com/wp-content/themes/virtue/assets/js/vendor/modernizr.min.js'></script>
<link rel="EditURI" type="application/rsd+xml" title="RSD" href="http://www.greenenergy-money.com/xmlrpc.php?rsd" />
<link rel="wlwmanifest" type="application/wlwmanifest+xml" href="http://www.greenenergy-money.com/wp-includes/wlwmanifest.xml" /> 
<meta name="generator" content="WordPress 4.1.4" />
<link rel='shortlink' href='http://www.greenenergy-money.com/?p=1100' />
<script type='text/javascript' src='http://www.greenenergy-money.com/wp-includes/js/jquery/ui/core.min.js?ver=1.11.2'></script>
<script type='text/javascript' src='http://www.greenenergy-money.com/wp-includes/js/jquery/ui/widget.min.js?ver=1.11.2'></script>
<script type='text/javascript' src='http://www.greenenergy-money.com/wp-includes/js/jquery/ui/button.min.js?ver=1.11.2'></script>
<script type='text/javascript' src='http://www.greenenergy-money.com/wp-includes/js/jquery/ui/mouse.min.js?ver=1.11.2'></script>
<script type='text/javascript' src='http://www.greenenergy-money.com/wp-includes/js/jquery/ui/resizable.min.js?ver=1.11.2'></script>
<script type='text/javascript' src='http://www.greenenergy-money.com/wp-includes/js/jquery/ui/draggable.min.js?ver=1.11.2'></script>
<script type='text/javascript' src='http://www.greenenergy-money.com/wp-includes/js/jquery/ui/position.min.js?ver=1.11.2'></script>
<script type='text/javascript' src='http://www.greenenergy-money.com/wp-includes/js/jquery/ui/dialog.min.js?ver=1.11.2'></script>
</script>

<style type="text/css">#logo {padding-top:25px;}#logo {padding-bottom:10px;}#logo {margin-left:0px;}#logo {margin-right:0px;}#nav-main {margin-top:40px;}#nav-main {margin-bottom:10px;}.headerfont, .tp-caption {font-family:Lato;} 
  .topbarmenu ul li {font-family:Lato;}


 #nav-second ul.sf-menu a:hover, .footerclass a:hover, .posttags a:hover, .subhead a:hover, .nav-trigger-case:hover .kad-menu-name, 
  .nav-trigger-case:hover .kad-navbtn, #kadbreadcrumbs a:hover, #wp-calendar a, .star-rating {color: #193874;}
.widget_price_filter .ui-slider .ui-slider-handle, .product_item .kad_add_to_cart:hover, .product_item:hover a.button:hover, .product_item:hover .kad_add_to_cart:hover, .kad-btn-primary, html .woocommerce-page .widget_layered_nav ul.yith-wcan-label li a:hover, html .woocommerce-page .widget_layered_nav ul.yith-wcan-label li.chosen a,
.product-category.grid_item a:hover h5, .woocommerce-message .button, .widget_layered_nav_filters ul li a, .widget_layered_nav ul li.chosen a, .wpcf7 input.wpcf7-submit, .yith-wcan .yith-wcan-reset-navigation,
#containerfooter .menu li a:hover, .bg_primary, .portfolionav a:hover, .home-iconmenu a:hover, p.demo_store, .topclass, #commentform .form-submit #submit, .kad-hover-bg-primary:hover, .widget_shopping_cart_content .checkout,
.login .form-row .button, .variations .kad_radio_variations label.selectedValue, #payment #place_order, .wpcf7 input.wpcf7-back, .shop_table .actions input[type=submit].checkout-button, .cart_totals .checkout-button, input[type="submit"].button, .order-actions .button  {background: #193874;}#containerfooter h3, #containerfooter, .footercredits p, .footerclass a, .footernav ul li a {color:#ffffff;}.headerclass {background:transparent    ;}.footerclass {background:#193874    ;}.product_item .product_details h5 {text-transform: none;}.product_item .product_details h5 {min-height:40px;}[class*="wp-image"] {-webkit-box-shadow: none;-moz-box-shadow: none;box-shadow: none;border:none;}[class*="wp-image"]:hover {-webkit-box-shadow: none;-moz-box-shadow: none;box-shadow: none;border:none;}</style>
<!-- Dynamic Widgets by QURL - http://www.qurl.nl //-->
<style type="text/css">.broken_link, a.broken_link {
	text-decoration: line-through;
}</style><style type="text/css" media="print">#wpadminbar { display:none; }</style>
<style type="text/css" media="screen">
	html { margin-top: 32px !important; }
	* html body { margin-top: 32px !important; }
	@media screen and ( max-width: 782px ) {
		html { margin-top: 46px !important; }
		* html body { margin-top: 46px !important; }
	}
</style>
<!--[if lt IE 9]>
<script src="http://www.greenenergy-money.com/wp-content/themes/virtue/assets/js/vendor/respond.min.js"></script>
<![endif]-->
<style type="text/css" title="dynamic-css" class="options-output">header #logo a.brand,.logofont{font-family:Lato;line-height:40px;font-weight:400;font-style:normal;font-size:32px;}.kad_tagline{font-family:Lato;line-height:20px;font-weight:400;font-style:normal;color:#444444;font-size:14px;}.product_item .product_details h5{font-family:Lato;line-height:20px;font-weight:700;font-style:normal;font-size:16px;}h1{font-family:Lato;line-height:40px;font-weight:400;font-style:normal;font-size:38px;}h2{font-family:Lato;line-height:40px;font-weight:normal;font-style:normal;font-size:32px;}h3{font-family:Lato;line-height:40px;font-weight:400;font-style:normal;font-size:28px;}h4{font-family:Lato;line-height:40px;font-weight:400;font-style:normal;font-size:24px;}h5{font-family:Lato;line-height:24px;font-weight:700;font-style:normal;font-size:18px;}body{font-family:Verdana, Geneva, sans-serif;line-height:20px;font-weight:400;font-style:normal;font-size:14px;}#nav-main ul.sf-menu a{font-family:Lato;line-height:18px;font-weight:700;font-style:normal;color:#193874;font-size:16px;}#nav-second ul.sf-menu a{font-family:Lato;line-height:22px;font-weight:400;font-style:normal;font-size:18px;}.kad-nav-inner .kad-mnav, .kad-mobile-nav .kad-nav-inner li a,.nav-trigger-case{font-family:Lato;line-height:20px;font-weight:400;font-style:normal;font-size:16px;}</style>

<link rel='stylesheet' href='report.css' type='text/css' media='all' />  
<link rel='stylesheet' href='css/tooltip-box.css' type='text/css' media='all' /> 
</head>
<?php 
/*******************
 *  END HTML HEAD  *
 *******************/
?>


<?php 
/*********************
 *  BEGIN HTML BODY  *
 *********************/
?>
<body class="page page-id-1100 page-template-default logged-in admin-bar no-customize-support wide ?page_id=1100">
<div id="wrapper" class="container">
	<div id="kt-skip-link">
		<a href="#content">Skip to Main Content</a>
	</div>

<header class="banner headerclass" role="banner"> 
  	<section id="topbar" class="topclass"> 
		<div class="container">  
			<div class="row"> 
				<div class="col-md-6 col-sm-6 kad-topbar-left">  
					<div class="topbarmenu clearfix"></div> 
				</div><!-- /.kad-topbar-left -->  
				<div class="col-md-6 col-sm-6 kad-topbar-right"> 
					<div id="topbar-search" class="topbar-widget">
						<form role="search" method="get" id="searchform" class="form-search" action="http://www.greenenergy-money.com/"> 
							<label class="hide" for="s">Search for:</label> 
							<input type="text" value="" name="s" id="s" class="search-query" placeholder="Search"> 
							<button type="submit" id="searchsubmit" class="search-icon"><i class="icon-search"></i></button>
						</form>   
					</div> 
				</div> <!-- /.kad-topbar-right-->  
			</div> <!-- /.row --> 
 		</div> <!-- /.container --> 
	</section> 
	<div class="container"> 
		<div class="row"> 
			<div class="col-md-4 clearfix kad-header-left"> 
				<div id="logo" class="logocase"> 
					<a class="brand logofont" href="http://www.greenenergy-money.com/"> 
						<div id="thelogo"> 
							<img src="http://www.greenenergy-money.com/wp-content/uploads/GEM-Logo-DD300x250.png" alt="Green Energy Money" class="kad-standard-logo" /> 
						</div> 
					</a> 
				</div><!-- /#logo --> 
			</div><!-- /.kad-header-left--> 
			<div class="col-md-8 kad-header-right"> 
<nav id="nav-main" class="clearfix" role="navigation"> 
	<ul id="menu-primary-nav" class="sf-menu"> 
		<li class="menu-gem-home sf-dropdown menu-item-954"><a href="http://www.greenenergy-money.com/">GEM Home</a> 
			<ul class="sf-dropdown-menu"> 
				<li class="menu-about-green-appraisals menu-item-1087"><a href="http://www.greenenergy-money.com/about-green-appraisals/">About Green Appraisals</a></li>
				<li class="menu-case-studies menu-item-1084"><a href="http://www.greenenergy-money.com/casestudy/">Case Studies</a></li>
			</ul> 
		</li> 
		<li class="menu-homeowners sf-dropdown menu-item-352"><a href="http://www.greenenergy-money.com/homeowners/">Homeowners</a> 
			<ul class="sf-dropdown-menu"> 
				<li class="menu-what-is-my-free-instant-report sf-dropdown-submenu menu-item-1099"><a href="http://www.greenenergy-money.com/what-is-my-free-instant-report/">What is my Free Instant Report?</a> 
					<ul class="sf-dropdown-menu"> 
						<li class="menu-free-report menu-item-1079"><a href="http://www.greenenergy-money.com/free-report/">Free Report</a></li>
					</ul>
				</li>
				<li class="menu-gem-home-calculators sf-dropdown-submenu menu-item-1091"><a href="#">GEM Home Calculators</a>
					<ul class="sf-dropdown-menu">
						<li class="menu-loan-to-value-ltv-calculator menu-item-1078"><a href="http://www.greenenergy-money.com/loan-to-value-calculator/">Loan to Value (LTV) Calculator</a></li>
						<li class="menu-incremental-property-value-ipv-calculator menu-item-1082"><a href="http://www.greenenergy-money.com/incremental-property-value-calculator/">Incremental Property Value (IPV) Calculator</a></li>
						<li class="menu-accelerated-mortgage-payment-amp-calculator menu-item-1081"><a href="http://www.greenenergy-money.com/accelerated-mortgage-payment-calculator/">Accelerated Mortgage Payment (AMP) Calculator</a></li>
					</ul>
				</li>
				<li class="menu-financing-new-construction menu-item-1090"><a href="http://www.greenenergy-money.com/financing-new-construction/">Financing New Construction</a></li>
			</ul>
		</li>
		<li class="menu-real-estate-agents menu-item-390"><a href="http://www.greenenergy-money.com/real-estate-agents/">Real Estate Agents</a></li>
		<li class="menu-builders sf-dropdown menu-item-342"><a href="http://www.greenenergy-money.com/pilot-program/">Builders</a>
			<ul class="sf-dropdown-menu">
				<li class="menu-commercial-loan-application menu-item-1080"><a href="http://www.greenenergy-money.com/pilot-program/commercial-loan-document-checklist/">Commercial Loan Application</a></li>
			</ul>
		</li>
		<li class="menu-about-gem sf-dropdown menu-item-955"><a href="http://www.greenenergy-money.com/about-us/">About GEM</a>
			<ul class="sf-dropdown-menu">
				<li class="menu-contact-us menu-item-981"><a href="http://www.greenenergy-money.com/contact-us/">Contact Us</a></li>
				<li class="menu-what-people-say-about-gem menu-item-1083"><a href="http://www.greenenergy-money.com/what-people-say-about-gem/">What People Say About GEM</a></li>
			</ul>
		</li>
		<li class="menu-blog menu-item-28"><a href="http://www.greenenergy-money.com/blog/">Blog</a></li>
	</ul><!-- /.menu-primary-nav -->
</nav> 
			</div><!-- /.kad-header-right--> 
               
    </div> <!-- Close Row -->
     
  </div> <!-- Close Container -->
   
</header>      

<div class="wrap contentclass" role="document">
	<div id="content" class="container">
		<div class="row">
			<div class="main col-md-12" role="main">
				<div class="entry-content" itemprop="mainContentOfPage" style="background-color:#0000bb;"> 
					<p id="notice"></p> 
					<p id="alert"><? if(isset($errors)){ var_dump($errors); }?></p>
					
					<?php if (isset($form) && strlen($form) > 0): ?>
						<div id='content' class='col-lg-9 col-md-8' style='background-color:#bbbbbb;'> 
							<div id='free-report-form' class='sidebar'>
						<? echo($form); ?>  
							</div>
						</div><!-- /#content --> 
						<aside class="col-lg-3 col-md-4 kad-sidebar" role="complementary" style="background-color:#bb0000;"> 
							<div id='free-report-sidebar'> 	
								<? if(isset($sidebar)){echo($sidebar);} ?> 
							</div><!-- /.sidebar -->
						</aside><!-- /aside -->
					<?php elseif (isset($report) && strlen($report) > 0): ?>
						<div id='content' class=' col-md-12' style='background-color:#bbbbbb;'> 
							<div id='free-report' style='background-color:#00bb00;'> 
								<?php echo($report); ?> 
							</div> 
						</div>
					<?php else : ?>
						ERROR
					<?php endif; ?>
					
					
				</div><!-- /.entry-content --> 
			</div><!-- /.main --> 
		</div><!-- /.row-->  
        </div><!-- /.content --> 
</div><!-- /.wrap -->  

<footer id="containerfooter" class="footerclass" role="contentinfo">
	<div class="container">
		<div class="row">
  		 	<div class="col-md-3 col-sm-6 footercol1">
				<aside class="widget-1 widget-first footer-widget"><aside id="virtue_about_with_image-2" class="widget virtue_about_with_image">
    					<div class="kad_img_upload_widget">
                				<img src="http://www.greenenergy-money.com/wp-content/uploads/Equal-Housing-Opportunity-e1430368341987.jpg" />
                    			</div>
				</aside>
			</div>
		</div><!-- /.row-->
	</div><!-- /.container -->
	<div class="footercredits clearfix">
		<div class="footernav clearfix">
			<ul id="menu-footer-nav" class="footermenu">
				<li  class="menu-about-green-energy-money menu-item-982"><a href="http://www.greenenergy-money.com/about-us/">About Green Energy Money</a></li>
				<li  class="menu-board-of-directors menu-item-983"><a href="http://www.greenenergy-money.com/about-us/board-of-directors/">Board of Directors</a></li>
				<li  class="menu-case-studies menu-item-984"><a href="http://www.greenenergy-money.com/casestudy/">Case Studies</a></li>
				<li  class="menu-contact-us menu-item-985"><a href="http://www.greenenergy-money.com/contact-us/">Contact Us</a></li>
			</ul>
		</div><!-- /.footernav -->
		<p>Copyright © 2015 | Green Energy Money </p>
	</div><!-- /.footercredits -->
</footer>
		    </div><!--/.wrapper-->
  </body>

<?php 
/*******************
 *  END HTML BODY  *
 *******************/
?>
</html>
