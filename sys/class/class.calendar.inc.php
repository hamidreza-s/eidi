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
		
		// Load events data
		$events = $this->_createEventObj();
		
         // Create the calendar markup
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

			//clear event_info variable 
			// for day that doesn't belong to current month
			$event_info = null; 
			
            // Add the day of the month to identify the calendar box
            if ($this->_startDay < $i && $this->_daysInMonth >= $c)
            {
				// Format events data
				if (isset($events[$c]))
				{
					foreach ($events[$c] as $event)
					{
						$link = '<a href="view.php?event_id='
							. $event->id
							. '">'
							. $event->title
							. '</a>';
						$event_info .= "\n\t\t\t$link";
					}
				}
				
				// store day number with two digits e.g 01 or 23
	            $date = sprintf("\n\t\t\t<strong>%02d</strong>", $c++);
            }
            else 
            { 
            	$date = "&nbsp;"; 
            }

            // If the current day is a Saturday, wrap to the next row
            $wrap = $i != 0 && $i%7 == 0 ? "\n\t</ul>\n\t<ul>" : NULL;

            // Assemble the pieces into a finished item
            $html .= $ls . $date . $event_info . $le . $wrap;
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
	
	/**
	* Returns a single event object
	*
	* @param ini $id an event ID
	* @param object the event object
	*/
	
	private function _loadEventById($id)
	{
		// If no ID is passed, return null
		if (empty($id))
		{
			return null;
		}
		
		// Load the events info array
		$event = $this->_loadEventData($id);
		
		// Return an event object
		if (isset($event[0]))
		{
			return new Event($event[0]);
		}
		else
		{
			return null;
		}
	}

	/**
	* Displays a given event's information
	*
	* @param ini $id the event ID
	* @return string basic markup to display the event info
	*/	
	
	public function displayEvents($id)
	{
		// Make sure an ID was passed
		if (empty($id))
		{
			return null;
		}
		
		// Make sure the ID is an integer
		$id = preg_replace('/[^0-9]/', '', $id);
		
		// load the event data from the DB
		
		$event = $this->_loadEventById($id);
		
		// Generate strings for the data, start, and end time
		$ts = strtotime($event->start);
		$date = date('F d, Y', $ts);
		$start = date('g:ia', $ts);
		$end = date('g:ia', strtotime($event->end));
		
		// Generate and return the markup
		return "<h2>$event->title</h2>"
			. "\n\t<p class=\"dates\">$date, $start&mdash;$end</p>"
			. "\n\t<p>$event->description</p>";
	}
	
	/**
	* Generate a form to edit or create events
	*
	* @return string the HTML markup for the editing form
	*/	
	public function displayForm()
	{
		// Check if an ID was passed
		if (isset($_POST['event_id']))
		{
			// Force integer type to sanitize data
			$id = (int) $_POST['event_id'];
		}
		else
		{
			$id = null;
		}
		
		// Instantiate the headline/submit button text
		$submit = "Create a New Event";
		
		// If an ID is passed, loads the associated event
		if (!empty($id))
		{
			$event = $this->_loadEventById($id);
			if (!is_object($event)) { return null; }
			
			$submit = "Edit This Event";
		}
		else
		{
			// Create a null event
			$event = new Event();
		}
		
		// Build the markup
		return <<<FORM_MARKUP
		
<form action="assets/inc/process.inc.php" method="post">
	<fieldset>
		<legend>$submit</legend>
		<label for="event_title">Event Title</label>
			<input type="text" name="event_title" id="event_title" value="$event->title" />
		<label for="event_start">Start Time</label>
			<input type="text" name="event_start" id="event_start" value="$event->start" />
		<lable for="event_end">End Time</label>
			<input type="text" name="event_end" id="event_end" value="$event->end" />
		<label for="event_description">Event Description</label>
			<textarea name="event_description" id="event_description">$event->description</textarea>
			<input type="hidden" name="event_id" value="$event->id" />
			<input type="hidden" name="token" value="$_SESSION[token]" />
			<input type="hidden" name="action" value="event_edit" />
			<input type="submit" name="event_submit" value="$submit" />
			 or <a href="./">cancel</a>
	</fieldset>
</form>
		
FORM_MARKUP;
	}
}










	











?>
