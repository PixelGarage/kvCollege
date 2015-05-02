<?php

require_once 'service/lib/HSO.php';

/**
 * Class HsoImporter
 *
 * This class contains all methods to import data from the HSO import interface.
 */
class HsoImporter
{
  private $hso;

  private $api_endpoint = "http://test:test@stage.hso.ch/sites/all/modules/hso_importer/service";

  private $use_service = false;

  private $live_import = false;

  public function webServiceUsed() {
    return $this->use_service;
  }

  public function liveImportConnected() {
    return $this->live_import;
  }

	public function init() {
    $this->live_import = variable_get('hso_importer_live_flag', 0) > 0;
    $this->hso = new HSO();
    $this->use_service = !$this->hso->initDB($this->live_import);
    // clear error message, if db could not be connected
    drupal_get_messages();

    // init DB connection
    if ($this->use_service) {
      // no direct DB connection possible, use web service
      watchdog("HSO Importer", "DB import system remotely connected", NULL, WATCHDOG_INFO);

    } else {
      // local DB connection
      watchdog("HSO Importer", "DB import system locally connected", NULL, WATCHDOG_INFO);

    }

	}

	/**
	 * Returns a JSON string object to the browser when hitting the root of the domain
	 *
	 * @url GET /
	 */
	public function ping()
	{
		return "Pong";
	}

	/**
	 * Gets the segments
	 *
	 * @url GET /segments
	 */
	public function getSegments()
	{
		$segments = array();
    if ($this->use_service) {
      // use web service for import
      $segments = $this->callWebService('/segments');

    } else {
      $segments = $this->hso->getSegments();

    }
		return $segments;
	}

	/**
	 * Gets the segment by id
	 *
	 * @url GET /segments/$id
	 */
	public function getSegment($id)
	{
		$segment = FALSE;
		$id = intval($id);
    if ($this->use_service) {
      // use web service for import
      $segment = $this->callWebService('/segments/' . $id);

    } else {
      // use direct db access
      $segment = $this->hso->getSegment($id);
    }

		return $segment;
	}
	
	/**
	 * Gets the contacts by segment
	 *
	 * @url GET /segments/$id/contacts
	 */
	public function getContactsBySegment($id)
	{
		$contacts = array();
		$id = intval($id);
    if ($this->use_service) {
      // use web service for import
      $contacts = $this->callWebService('/segments/' . $id . '/contacts');

    } else {
      // use direct db access
      $contacts = $this->hso->getContactsBySegment($id);
    }
		return $contacts;
	}

  /**
   * Gets the contacts of a course for the brand
   *
   * @url GET /brand/$brand/course/$id/contacts
   */
  public function getContactsOfCourseForBrand($brand, $id)
  {
    $contacts = array();
    $id = intval($id);
    if ($this->use_service) {
      // use web service for import
      $contacts = $this->callWebService('/brand/' . $brand . '/course/' . $id . '/contacts');

    } else {
      // use direct db access
      $contacts = $this->hso->getContactsOfCourseForBrand($brand, $id);
    }
    return $contacts;
  }

  /**
   * Gets the course id updates. This update has to be performed once.
   *
   * @url GET /courses/idupdates
   */
  public function getCoursesIDUpdates()
  {
    $course_ids = array();
    if ($this->use_service) {
      // use web service for import
      $course_ids = $this->callWebService('/courses/idupdates');

    } else {
      // use direct db access
      $course_ids = $this->hso->getCoursesIDUpdates();
    }
    return $course_ids;
  }

  /**
   * Gets the courses by segment
   *
   * @url GET /segments/$id/courses
   */
  public function getCoursesBySegment($id)
  {
    $courses = array();
    $id = intval($id);
    if ($this->use_service) {
      // use web service for import
      $courses = $this->callWebService('/segments/' . $id . '/courses');

    } else {
      // use direct db access
      $courses = $this->hso->getCoursesBySegment($id);
    }
    return $courses;
  }

  /**
	 * Gets the courses
	 *
	 * @url GET /courses
	 */
	public function getCourses()
	{
		$courses = array();
    if ($this->use_service) {
      // use web service for import
      $courses = $this->callWebService('/courses');

    } else {
      // use direct db access
      $courses = $this->hso->getCourses();
    }
		return $courses;
	}

	/**
	 * Gets the course by id
	 *
	 * @url GET /courses/$id
	 */
	public function getCourse($id)
	{
		$course = FALSE;
		$id = intval($id);
    if ($this->use_service) {
      // use web service for import
      $course = $this->callWebService('/courses/' . $id);

    } else {
      // use direct db access
      $course = $this->hso->getCourse($id);
    }
		return $course;
	}
	
	/**
	 * Gets the times of a course for a brand
	 *
	 * @url GET /brand/$brand/course/$id/times
	 */
	public function getTimesOfCourseForBrand($brand, $id)
	{
		$id = intval($id);
		$times = array();
    if ($this->use_service) {
      // use web service for import
      $times = $this->callWebService('/brand/' . $brand . '/course/' . $id . '/times');

    } else {
      // use direct db access
      $times = $this->hso->getTimesOfCourseForBrand($brand, $id);
    }
		return $times;
	}
	
	/**
	 * Gets the locations
	 *
	 * @url GET /locations
	 */
	public function getLocations()
	{
		$locations = array();
    if ($this->use_service) {
      // use web service for import
      $locations = $this->callWebService('/locations');

    } else {
      // use direct db access
      $locations = $this->hso->getLocations();
    }
		return $locations;
	}

	/**
	 * Gets the location by id
	 *
	 * @url GET /locations/$id
	 */
	public function getLocation($id)
	{
		$location = FALSE;
		$id = intval($id);
    if ($this->use_service) {
      // use web service for import
      $location = $this->callWebService('/locations/' . $id);

    } else {
      // use direct db access
      $location = $this->hso->getLocation($id);
    }
		return $location;
	}

  /**
   * Call the web service with a specific url.
   * @param $relative_url string Relative url to call on Rest server.
   *
   * @return mixed Returns the result of the web service call.
   */
  private function callWebService($relative_url) {
    $result = null;
    $url = $this->api_endpoint . $relative_url;
    $url .= $this->live_import ? '/?livedb=1' : '';
    $request = drupal_http_request($url);
    if ($request->code == 200 && !empty($request->data)) {
      $result = json_decode($request->data);
    }

    return $result;
  }
}