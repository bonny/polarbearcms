<?php
/**
 * Class for observers
 */
class pb_observer {

	static $observed = array();
	
	/**
	 * Add something to observe/watch
	 * @param $what string what to observer
	 */
	public function attach($event, $function, $prio=50) {

		if (!isset($this->observed[$event])) {
			$this->observed[$event] = array();
		}
		$this->observed[$event][] = array(
			"what" => $event,
			"function" => $function,
			"prio" => $prio
		);

	}
	
	/**
	 * $arrArgs är en array
	 * hur vet vi vad vi ska returnera när vi loopar igenom alla events för en grej?
	 * t.ex. output buffer vill ha $buffer, men den skickar vi in i array och måste skicka
	 * in som array i nästa loop också...
	 */
	public function fire($event, $arrArgs = null) {
		if (!is_array($arrArgs)) {
			$arrArgs = array();
		}
		
		// auto add some stuff to the arguments-array
		global $polarbear_render_start_ms;
		$arrArgs["pb_microtime_since_start"] = microtime(true) - $polarbear_render_start_ms;
				
		// find and fire handler for event
		$contentToReturn = null;
		if (isset($this->observed[$event])) {
			// actions for event exists, fire them
			// @todo: fire them in prio order
			foreach ($this->observed[$event] as $oneEvent) {
				if (is_callable($oneEvent["function"])) {
					$contentToReturn = call_user_func($oneEvent["function"], $arrArgs);
				}
			}
		}
		return $contentToReturn;
	}

	public function test() {
		return "test here!";
	}

}
?>