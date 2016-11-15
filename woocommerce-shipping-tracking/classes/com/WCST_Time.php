<?php 
class WCST_Time
{
	public function __construct()
	{
	}
	public function get_available_date($estimation_rule)
	{
		if(!isset($estimation_rule))
			return "";
		
		$wcst_option_model = new WCST_Option();
		$hour_offset = $wcst_option_model->get_estimations_options('hour_offset', 0);
		$date_format = $wcst_option_model->get_option('wcst_general_options', 'date_format', "dd/mm/yyyy");
		//$now =  date($date_format,strtotime($hour_offset.' hours'));
		$hour =  date('G',strtotime($hour_offset.' hours'));
		$start_day_of_the_week =  date('w',strtotime($hour_offset+($estimation_rule['day_cut_off_hour']*24).' hours')); //0 (for Sunday) through 6 (for Saturday)
		$start_day_of_the_month =  date('j',strtotime($hour_offset+($estimation_rule['day_cut_off_hour']*24).' hours'));
		$start_month =  date('n',strtotime($hour_offset+($estimation_rule['day_cut_off_hour']*24).' hours'));
		$start_year =  date('Y',strtotime($hour_offset+($estimation_rule['day_cut_off_hour']*24).' hours'));
		/* 
		* Format:
		["day_cut_off_hour"]=>
			  string(1) "0"
			  ["days_delay"]=>
			  string(1) "0"
			}
		*/
		//it like placing an order in the 24h before
		if($hour < $estimation_rule['day_cut_off_hour'])
		{
			$start_day_of_the_week =  date('w',strtotime(($hour_offset-24)+($estimation_rule['day_cut_off_hour']*24).' hours'));
			$start_day_of_the_month =  date('j',strtotime(($hour_offset-24)+($estimation_rule['day_cut_off_hour']*24).' hours'));
			$start_month =  date('n',strtotime(($hour_offset-24)+($estimation_rule['day_cut_off_hour']*24).' hours'));
			$start_year =  date('Y',strtotime(($hour_offset-24)+($estimation_rule['day_cut_off_hour']*24).' hours'));
		}
		$starting_date = array('day_of_the_week' => $start_day_of_the_week, 'day_of_the_month' => $start_day_of_the_month, 'month'=>$start_month, 'year' => $start_year);
		//wcst_var_dump($estimation_rule);
		do
		{
			$starting_date = $this->get_next_available_date($starting_date,$estimation_rule);
			$is_non_working_day = $this->check_if_date_is_a_non_working_day($starting_date,$estimation_rule);
		}while($is_non_working_day);
		
		//wcst_var_dump($starting_date);
		$date = new DateTime($starting_date['year']."-".$starting_date['month']."-".$starting_date['day_of_the_month']);
		if( $date_format = "dd/mm/yyyy" )
			return $date->format("d/m/Y");
		
		return $date->format("m/d/Y");
	}
	private function get_next_available_date($starting_date, $estimation_rule)
	{
		/* Format:
			["working_days"]=> //0 (for Sunday) through 6 (for Saturday)
			  array(2) {
				[0]=>
				string(1) "2"
				[1]=>
				string(1) "5"
			  }
			  */
		$found = false;	
		//$number_of_days_added = 0;
		do
		{
			//$number_of_days_added++;
			$starting_date_to_string = $starting_date['year']."-".$starting_date['month']."-".$starting_date['day_of_the_month'];
			$starting_date['day_of_the_week'] = (++$starting_date['day_of_the_week'])%7;
			
			$starting_date["day_of_the_month"] = date('j', strtotime($starting_date_to_string. ' + 1 days'));
			$starting_date["month"] = date('n', strtotime($starting_date_to_string. ' + 1 days'));
			$starting_date["year"] = date('Y', strtotime($starting_date_to_string. ' + 1 days'));
			
			foreach($estimation_rule["working_days"] as $working_days)
			{
				if($starting_date['day_of_the_week'] == $working_days)
					$found = true;
			}
		}while(!$found);
			
		return $starting_date;
	}
	private function check_if_date_is_a_non_working_day($date, $estimation_rule)
	{
		/*Format:
		  ["non_working_days"]=>
		  array(2) {
			[0]=>
			array(2) {
			  ["day"]=>
			  string(1) "1"
			  ["month"]=>
			  string(1) "3"
			}
			[1]=>
			array(2) {
			  ["day"]=>
			  string(1) "1"
			  ["month"]=>
			  string(2) "11"
			}
		  }
		  */
		if(is_array($estimation_rule["non_working_days"]))
			foreach($estimation_rule["non_working_days"] as $non_working_days)
			{
				if($date['day_of_the_month'] == $non_working_days['day'] && $date['month'] == $non_working_days['month'])
				return true;
					
			}
			return false;
	}
}
?>