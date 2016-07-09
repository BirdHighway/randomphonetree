<?php
session_start();
// We'll need to keep track of which number each option was listed with when the call is transferred to the next php page
// Losing track of what pressing 5 means, for example, could be detrimental to the caller's experience

header("content-type: text/xml");

/*
	The order the options are listed in will be random
	BUT we don't want the same order to be used twice in a row
	To better serve our customers, we'll keep track of the most recent order used
	In the future we may want to make sure a customer never gets the same order,
	So we'll keep track of which phone numbers get which orders - just in case
	If you're following along:
		CREATE DATABASE customerservice;
		USE customerservice;
		CREATE TABLE 
			optionorder (id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			sequence VARCHAR(10) NOT NULL, 
			callerphone INT(10) INSIGNED NOT NULL,
			calltime TIMESTAMP);
	We'll add a sample row so there's a value for the first caller
		INSERT INTO optionorder (sequence,callerphone) VALUES ("0123456789",12164100000);
	You'll also need to get a phone number from Twilio and set its voice request URL to this file
*/

class CallerOption {
	public $message;
	public $id_number;
	public $voice_attributes;
	function __construct($message,$id_number,$voice_attributes){
		$this->message = $message;
		$this->id_number = $id_number;
		$this->voice_attributes = $voice_attributes;
	}
}

function get_order($options_array){
	$order_string = "";
	foreach($options_array as $option){
		$option_id = $option->id_number;
		$order_string = $order_string . $option_id;
	}
	return $order_string;
}

function offer_option($option_object,$key_to_press){
	$message = $option_object->message;
	$id_number = $option_object->id_number;
	$voice_attributes = $option_object->voice_attributes;
	echo <<<_END
	<Say $voice_attributes>
		$message$key_to_press
	</Say>
_END;
}

function full_response($conn,$options_array,$options_order,$from_number){
	echo <<<_END
	<Response>
		<Say>
		Please listen carefully as the order of our menu options is completely random.
		</Say>
		<Gather action='voice-process.php' method='GET' numDigits='1'>
_END;
		for($i=0;$i<10;$i++){
			$option = $options_array[$i];
			offer_option($option,$i);
		}
	echo <<<_END
		<Pause/>
		<Say>
		Okay, don't pick a number. Good bye.
		</Say>
		</Gather>
		<Hangup/>
	</Response>
_END;
	// Set Session Variable
	$_SESSION['sequence'] = $options_order;
	// Update MySql with latest order
	if( $stmt = $conn->prepare("INSERT INTO optionorder (sequence, callerphone) VALUES(?,?)") ){
		$stmt->bind_param("si",$options_order,$from_number);
		$stmt->execute();
		$stmt->close();
	}
}

$options_array = array();
$options_array[] = new CallerOption("For sales, press ",0,"");
$options_array[] = new CallerOption("For customer service, press ",1,"");
$options_array[] = new CallerOption("For mergers and aquisitions, press ",2,"");
$options_array[] = new CallerOption("For news and events, press ",3,"");
$options_array[] = new CallerOption("For more options, press ",4,"");
$options_array[] = new CallerOption("For Lauren, press ",5," voice='alice'");
$options_array[] = new CallerOption("For Ralph, press ",6,"");
$options_array[] = new CallerOption("If this number called you, press ",7,"");
$options_array[] = new CallerOption("Para Español, oprima el numero ",8," language='es-MX'");
$options_array[] = new CallerOption("To be placed on hold, press ",9,"");

shuffle($options_array);



$conn = new mysqli("localhost", "root", "4rtP3wX7?m", "customerservice");
if($conn->connect_error){
	// If we have an issue connecting to our database, we'll inform our callers
	// Surely they will alert our tech department
	$message = $conn->connect_error;
	echo <<<_END
	<Response>
		<Say>
			Oh no! It looks like there was a my s q l error!
			Please relay the following to our tech department:
			$message
		</Say>
	</Response>	

_END;
}else{
	// Store the caller's phone number in $from_number
	$from_number = $_REQUEST['From'];
	// Get the value of the most recent order
	$query = "SELECT * FROM optionorder ORDER BY id DESC LIMIT 1";
	$result = $conn->query($query);
	$info = $result->fetch_array(MYSQLI_ASSOC);
	$latest_order = $info['sequence'];

	$options_order = get_order($options_array);
	if( $options_order != $latest_order ){
		full_response($conn,$options_array,$options_order,$from_number);
	}else{
		shuffle($options_array);
		$options_order = get_order($options_array);
		full_response($conn,$options_array,$options_order,$from_number);
	}


}

?>
