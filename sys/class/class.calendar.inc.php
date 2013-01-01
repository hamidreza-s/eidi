<?php
/**
 * Build and manipulates an events calendar
 * PHP Version 5
 *
 * LICENSE: This source file is subject to the GPLv2 License, available
 * at http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @author			Hamidreza Soleimani <hamidreza.s@gmail.com>
 * @copyright		2013 MeloBit Co.
 * @license			http://www.gnu.org/licenses/gpl-2.0.html
 */

class Calendar extends DB_Connect
{
	/**
	* The date from which the calendar should be built
	*
	* Stored in YYYY-MM-DD HH:MM:SS
	*
	* @var string the date to use for calendar
	*/
	private $_useDate;

	/**
	* The month for which the calendar is being built
	*
	* $var int the month being used
	*/
	private $_m;

	/**
	* The year from which the month's start day is selected
	*
	* @var int the year being used
	*/
	private $_y;

	/**
	* The number of days in the month being used
	*
	* @var int the number of days in the month
	*/
	private $_daysInMonth;

	/**
	* The index of the day of the week the month starts on (0-6)
	*
	* @var int the day of the week the month starts on
	*/
	private $_startDay;

	/**
	* Creates a database object and stores relevant data
	*
	* Upon instantiation, this class accepts a database object
	* that, if not null, is stored in the object's private $_db
	* property. If null, a new PDO object is created and stored
	* instead.
	*
	* Addintional info is gathered and stored in this method,
	* including the month from which the calendar is to be built,
	* how many days are in said month, what day the month starts
	* on, and what day it is currently.
	*
	* @param object $dbo a database object
	* @param string $useDate the date to use to build the calendar
	* @return void
	*/
	public function __construct($db = null, $useDate = null)
	{
		// Call the parent constructor to check for db object
		parent::__construct($db);
		
		// Gather and store data relevant to the mont
		if (isset($useDate))
		{
			$this->_useDate = $useDate;
		}
		else
		{
			$this->_useDate = date('Y-m-d H:i:s');
		}
		
		// Convert to Timestamp,
		// then determine the month and year to use
		// when building the calendar
		$ts = strtotime($this->_useDate);
		$this->_m = date('m', $ts);
		$this->_y = date('Y', $ts);
		
		// Determine how many days are in the month
		$this->_daysInMonth = cal_days_in_month(
			CAL_GREGORIAN,
			$this->_m,
			$this->_y
		);
		
		// Determine what weekday the month starts on
		$ts = mktime(0, 0, 0, $this->_m, 1, $this->_y);
		$this->_startDay = date('w', $ts);
		
	}
	
	/**
	* Loads event(s) info into an array 
	*
	* @param int $id an optional event ID to filter results
	* @return array an array of events from the database
	*/
	public function _loadEventData($id = null)
	{
		$sql = 'SELECT
							`event_id`, `event_title`, `event_desc`,
							`event_start`, `event_end`
						FROM
							`events`';
		// If an event is supplied, add a WHERE clause
		// so only that is returned
		if (!empty($id))
		{
			$sql .= 'WHERE `event_id` = :id LIMIT 1';
		}
		// Otherwise, load all events for the month in use
		else
		{
			// Find the first and last days of the month
			$start_ts = mktime(0, 0, 0, $this->_m, 1, $this->_y);
			$end_ts = mktime(0, 0, 0, $this->_m + 1, 0, $this->_y);
			$start_date = date('Y-m-d H:i:s', $start_ts);
			$end_date = date('Y-m-d H:i:s', $end_ts);
			
			// Filter events to only those happening in the
			// currently selected month
			$sql .= "WHERE `event_start`
								BETWEEN '$start_date' AND '$end_date'
								ORDER BY `event_start`";
		}
		
		try
		{
			$stmt = $this->db->prepare($sql);
			
			// Bind the parameter if and ID was passed
			if (!empty($id))
			{
				$stmt->bindParam(":id", $id, PDO::PARAM_INT);			
			}
			
			$stmt->execute();
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
			
			return $results;
		}
		catch (Exception $e)
		{
			die ($e->getMessage());
		}
	}

	/**
	* Load all eventsfor the month into an array 
	*
	* @return array events info
	*/
	private function _createEventObj()
	{
		// Load the event array
		$eventsArray = $this->_loadEventData();
		
		// Create month array, then organize the events
		// by the day of the month
		$monthArray = array();
		foreach ($eventsArray as $event)
		{
			$day = date('j', strtotime($event['event_start']));
			
			try
			{
				$monthArray[$day][] = new Event($event);
			}
			catch (Exception $e)
			{
				die ($e->getMessage());
			}
		}
		
		return $monthArray;
	}
	
	/**
	* Returns HTML markup to display the calendar and events
	*
	* Using the information stored in class properties, the
	* events for the given month are loaded, the calendar is
	* generated, and the whole thing is returned as valid markup.
	*
	* @return string the calendar HTML markup
	*/
	public function buildCalendar()
	{
		// Determine the calendar month and create an array of
		// weekday abbreviations to lable the calendar columns
		$cal_month = date('F Y', strtotime($this->_useDate));
		$weekdays = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
		
		// Add header to the calendar markup
		$html = "\n\t<h2>$cal_month</h2>";
		for ($d = 0, $labels = null; $d < 7; ++$d)
		{
			$labels .= "\n\t\t<li>" . $weekdays[$d] . "</li>";
		}
		$html .= "\n\t<ul class=\"weekdays\">" . $labels . "\n\t</ul>";
		
        /*
         * Create the calendar markup
         */
        $html .= "\n\t<ul>"; // Start a new unordered list
        for ( $i=1, $c=1, $t = date('j'), $m = date('m'), $y = date('Y');
                $c <= $this->_daysInMonth; ++$i )
        {
            // Apply a "fill" class to the boxes occurring before
            // the first of the month
            $class = $i <= $this->_startDay ? "fill" : NULL;

            // Add a "today" class if the current date matches
            // the current date
            if ($c == $t && $m == $this->_m && $y == $this->_y )
            {
                $class = "today";
            }

            // Build the opening and closing list item tags
            $ls = sprintf("\n\t\t<li class=\"%s\">", $class);
            $le = "\n\t\t</li>";

            // Add the day of the month to identify the calendar box
            if ($this->_startDay < $i && $this->_daysInMonth >= $c)
            {
	            $date = sprintf("\n\t\t\t<strong>%02d</strong>", $c++);
            }
            else 
            { 
            	$date = "&nbsp;"; 
            }

            // If the current day is a Saturday, wrap to the next row
            $wrap = $i != 0 && $i%7 == 0 ? "\n\t</ul>\n\t<ul>" : NULL;

            // Assemble the pieces into a finished item
            $html .= $ls . $date . $le . $wrap;
        }

        // Add filler to finish out the last week
        while ($i%7 != 1)
        {
            $html .= "\n\t\t<li class=\"fill\">&nbsp;</li>";
            ++$i;
        }

        // Close the final unordered list
        $html .= "\n\t</ul>\n\n";

        // Return the markup for output
        return $html;
	}
}










	











?>
