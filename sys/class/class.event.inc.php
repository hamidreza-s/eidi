<?php

/**
 * Stores event information
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT License, available
 * at http://www.opensource.org/licenses/mit-license.html
 *
 * @author     Jason Lengstorf <jason.lengstorf@ennuidesign.com>
 * @copyright  2010 Ennui Design
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
class Event
{

    /**
     * The event ID
     *
     * @var int
     */
    public $id;

    /**
     * The event title
     *
     * @var string
     */
    public $title;

    /**
     * The event description
     *
     * @var string
     */
    public $description;

    /**
     * The event start time
     *
     * @var string
     */
    public $start;

    /**
     * The event end time
     *
     * @var string
     */
    public $end;

    /**
     * Accepts an array of event data and stores it
     *
     * @param array $event Associative array of event data
     * @return void
     */
    public function __construct($event = null)
    {
		if (is_null($event))
		{
			$this->id = null;
			$this->title = null;
			$this->description = null;
			$this->start = null;
			$this->end = null;		
		}	
        elseif ( is_array($event) )
        {
            $this->id = $event['event_id'];
            $this->title = $event['event_title'];
            $this->description = $event['event_desc'];
            $this->start = $event['event_start'];
            $this->end = $event['event_end'];
        }
        else
        {
            throw new Exception("No event data was supplied.");
        }
    }

}

?>