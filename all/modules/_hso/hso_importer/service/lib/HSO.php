<?php

class HSO
{
	private $db;

  private $connected = FALSE;

  /**
   * Initialisation via web service.
   */
	public function init() {
    $live_import = array_key_exists('livedb', $_GET);
    $this->initDB($live_import);
	}

  public function initDB($live_import = TRUE) {
    $db_name = $live_import ? 'hso_anmsys_new' : 'hso_anmsys_test';
    $this->db = new Mysqli('localhost', 'hsodrupaluser', 'megaphon', $db_name);

    // validate DB connection
    if ($this->db->connect_errno) {
      // no DB connection possible
      error_log("HSO Importer error: DB import system could not be connected", 0);
      $this->connected = FALSE;

    } else {
      // successful DB connection
      error_log("HSO Importer status: DB '" . $db_name . "' connected", 0);
      $this->db->query('SET NAMES utf8');
      $this->connected = TRUE;

    }

    return $this->connected;
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
	 * Gets the segments (departments)
	 *
	 * @url GET /segments
	 */
	public function getSegments()
	{
		$segments = array();
    if(!$this->connected) return $segments;

		$result = $this->db->query('SELECT department_id, department_long ' .
                                'FROM anm_departments ORDER BY department_id ASC');
		while ($row = $result->fetch_object()) {
			$segment = new StdClass();
			$segment->id = $row->department_id;
			$segment->title = $row->department_long;
			$segments[] = $segment;
		}
		return $segments;
	}

	/**
	 * Gets the segment (department) by department id
	 *
	 * @url GET /segments/$id
	 */
	public function getSegment($id)
	{
		$segment = FALSE;
    if(!$this->connected) return $segment;

    $id = intval($id);
		$result = $this->db->query('SELECT department_id, department_long ' .
                                'FROM anm_departments WHERE department_id = ' . $id);
		if ($row = $result->fetch_object()) {
			$segment = new StdClass();
			$segment->id = $row->department_id;
			$segment->title = $row->department_long;
		}
		return $segment;
	}
	
	/**
	 * Gets the contacts by segment (department)
	 *
	 * @url GET /segments/$id/contacts
	 */
	public function getContactsBySegment($id)
	{
		$contacts = array();
    if(!$this->connected) return $contacts;

    $id = intval($id);
		$result = $this->db->query('SELECT c.branch_id, c.department_id, c.show_value, c.contact_person, c.contact_tel, c.contact_email, c.anm_lehrgang_id, ' .
                              'b.branch_town, b.branch_address, b.branch_zip, b.branch_fax, b.branch_phone, b.branch_mail, b.brand_id, b.brand_short ' .
                              'FROM anm_contacts c JOIN anm_branches b ON (c.branch_id = b.branch_id) ' .
                              'WHERE c.department_id = ' . $id . ' ORDER BY b.branch_id, c.anm_lehrgang_id');
		while ($row = $result->fetch_object()) {
			$contact = new StdClass();
			$contact->id = $row->show_value;
			$contact->name = $row->contact_person;
			$contact->phone = $row->contact_tel;
			$contact->email = $row->contact_email;
      // create location object (branch)
			$contact->location = new StdClass();
			$contact->location->id = $row->branch_id;
			$contact->location->name = $row->branch_town;
			$contact->location->address = $row->branch_address;
			$contact->location->plz = $row->branch_zip;
			$contact->location->fax = $row->branch_fax;
			$contact->location->phone = $row->branch_phone;
			$contact->location->email = $row->branch_mail;
      $contact->location->brand_id = $row->brand_id;
      $contact->location->brand_short = $row->brand_short;
			$contacts[] = $contact;
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
    if(!$this->connected) return $contacts;

    $id = intval($id);
    $brand_id = intval($brand);

    // HSO: import all available contacts
    // all other brands: import all contacts of given brand and all contacts of HSO (brand_id = 1) that are located at brand locations
    $and_s_visible_for_brand = ($brand_id == 1) ? '' :
      ' AND (b.brand_id = ' . $brand_id . ' OR b.brand_id = 1 AND b.standort_id IN (SELECT bb.standort_id FROM anm_branches bb WHERE bb.brand_id = ' . $brand_id . '))';

    $result = $this->db->query('SELECT c.branch_id, c.department_id, c.show_value, c.contact_person, c.contact_tel, c.contact_email, c.lehrgang_id, ' .
                                'b.branch_town, b.branch_address, b.branch_zip, b.branch_fax, b.branch_phone, b.branch_mail, b.brand_id, b.brand_short ' .
                                'FROM anm_contacts c JOIN anm_branches b ON (c.branch_id = b.branch_id) ' .
                                'WHERE c.lehrgang_id = ' . $id . $and_s_visible_for_brand . ' ORDER BY b.brand_id, b.branch_id');
    while ($row = $result->fetch_object()) {
      $contact = new StdClass();
      // define unique internal id, no representation on server
      $contact->id = $row->show_value;
      $contact->name = $row->contact_person;
      $contact->phone = $row->contact_tel;
      $contact->email = $row->contact_email;
      $contact->picture = 'public://user_pictures/' . $row->show_value . '.jpg';
      // create location object (branch)
      $contact->location = new StdClass();
      $contact->location->id = $row->branch_id;
      $contact->location->name = $row->branch_town;
      $contact->location->address = $row->branch_address;
      $contact->location->plz = $row->branch_zip;
      $contact->location->fax = $row->branch_fax;
      $contact->location->phone = $row->branch_phone;
      $contact->location->email = $row->branch_mail;
      $contact->location->brand_id = $row->brand_id;
      $contact->location->brand_short = $row->brand_short;
      $contacts[] = $contact;
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
    if(!$this->connected) return $course_ids;

    $result = $this->db->query('SELECT old_id, new_id FROM anm_change_ids ORDER BY old_id ASC');
    while ($row = $result->fetch_object()) {
      $course_ids[$row->old_id] = $row->new_id;
    }
    return $course_ids;
  }

  /**
   * Gets the courses by segment (department)
   *
   * @url GET /segments/$id/courses
   */
  public function getCoursesBySegment($id)
  {
    $courses = array();
    if(!$this->connected) return $courses;

    $id = intval($id);
    $result = $this->db->query('SELECT lehrgang_id, department_id, webRegistrationTitle, webRegistrationTitleMeta, ' .
                                ' webRegistrationShortDescription, webRegistrationPageMeta, type ' .
                                'FROM anm_lehrgaenge ' .
                                'WHERE department_id = ' . $id . ' ORDER BY lehrgang_id ASC');
    while ($row = $result->fetch_object()) {
      $course = new StdClass();
      $course->id = $row->lehrgang_id;
      $course->segment_id = $row->department_id;
      $course->title = $row->webRegistrationTitle;
      $course->is_module = $row->type == 'MODUL';
      // create meta object
      $course->meta = new StdClass();
      $course->meta->title = $row->webRegistrationTitleMeta;
      $course->meta->description = $row->webRegistrationShortDescription;
      $course->meta->keywords = $row->webRegistrationPageMeta;
      $courses[] = $course;
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
    if(!$this->connected) return $courses;

    $result = $this->db->query('SELECT lehrgang_id, department_id, webRegistrationTitle, webRegistrationTitleMeta, ' .
                                ' webRegistrationShortDescription, webRegistrationPageMeta, type ' .
                                'FROM anm_lehrgaenge ORDER BY lehrgang_id ASC');
    while ($row = $result->fetch_object()) {
      $course = new StdClass();
      $course->id = $row->lehrgang_id;
      $course->segment_id = $row->department_id;
      $course->title = $row->webRegistrationTitle;
      $course->is_module = $row->type == 'MODUL';
      // create meta object
      $course->meta = new StdClass();
      $course->meta->title = $row->webRegistrationTitleMeta;
      $course->meta->description = $row->webRegistrationShortDescription;
      $course->meta->keywords = $row->webRegistrationPageMeta;
      $courses[] = $course;
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
    if(!$this->connected) return $course;

    $id = intval($id);
		$result = $this->db->query('SELECT lehrgang_id, department_id, webRegistrationTitle, webRegistrationTitleMeta, ' .
                                ' webRegistrationShortDescription, webRegistrationPageMeta, type ' .
                                'FROM anm_lehrgaenge WHERE lehrgang_id = ' . $id);
		if ($row = $result->fetch_object()) {
			$course = new StdClass();
      $course->id = $row->lehrgang_id;
      $course->segment_id = $row->department_id;
      $course->title = $row->webRegistrationTitle;
      $course->is_module = $row->type == 'MODUL';
      // create meta object
      $course->meta = new StdClass();
      $course->meta->title = $row->webRegistrationTitleMeta;
      $course->meta->description = $row->webRegistrationShortDescription;
      $course->meta->keywords = $row->webRegistrationPageMeta;
      $courses[] = $course;
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
		$times = array();
    if(!$this->connected) return $times;

    $id = intval($id);
    $brand_id = intval($brand);

    // HSO: import all available course times
    // all other brands: import all course times of given brand and all course times of HSO (brand_id = 1) that are located at brand locations
    $and_s_visible_for_brand = ($brand_id == 1) ? '' :
      ' AND (b.brand_id = ' . $brand_id . ' OR b.brand_id = 1 AND b.standort_id IN (SELECT bb.standort_id FROM anm_branches bb WHERE bb.brand_id = ' . $brand_id . '))';

    $query = 'SELECT a.df_id, a.start_date, a.start_time, a.end_date, a.durchf_text, a.lehrgang_id, a.nof_tn, a.price_brutto, a.price_netto, ' .
      'a.webTemplate, a.teilnehmer_min, a.teilnehmer_max, a.price_text, a.price_additional, ' .
      'b.branch_id, b.branch_town, b.branch_address, b.branch_zip, b.branch_fax, b.branch_phone, b.branch_mail, b.brand_id, b.brand_short ' .
      'FROM anm_durchfuehrungen_combined a ' .
      'LEFT JOIN anm_branches b ON (a.branch_id = b.branch_id) ' .
      'WHERE a.lehrgang_id = ' . $id . $and_s_visible_for_brand . ' ORDER BY a.start_date ASC';

    $result = $this->db->query($query);
		while ($row = $result->fetch_object()) {
			$time = new StdClass();
      $time->id = $row->df_id;
      $time->course_id = $row->lehrgang_id;
			$time->start_date = substr($row->start_date, 0, 10);
			$time->start_time = empty($row->start_time) ? NULL : str_replace('.', ':', substr(trim($row->start_time), 0, 5)) . ':00';
			$time->end_date = empty($row->end_date) || $row->end_date == '0000-00-00 00:00:00' ? null : substr($row->end_date, 0, 10);
			$time->description = $row->durchf_text;
			$time->has_subsidy = $row->price_brutto <> $row->price_netto;
      $time->price_brutto = $row->price_brutto;
      $time->price_netto = $row->price_netto;
      $time->price_detailed = $row->price_text;
      $time->price_additional = $row->price_additional;
      $time->taken_places = $row->nof_tn == -1 ? 0 : $row->nof_tn;
      $time->no_vacancy = $row->nof_tn == -1;
      $time->min_places = $row->teilnehmer_min;
      $time->max_places = $row->teilnehmer_max;
      $time->template = $row->webTemplate;
      $time->brand_id = $row->brand_id;
      // create location object (branch)
      $time->location = new StdClass();
      $time->location->id = $row->branch_id;
      $time->location->name = $row->branch_town;
      $time->location->address = $row->branch_address;
      $time->location->plz = $row->branch_zip;
      $time->location->fax = $row->branch_fax;
      $time->location->phone = $row->branch_phone;
      $time->location->email = $row->branch_mail;
      $time->location->brand_id = $row->brand_id;
      $time->location->brand_short = $row->brand_short;
			$times[] = $time;
		}
		return $times;
	}
	
	/**
	 * Gets the locations (branches)
	 *
	 * @url GET /locations
	 */
	public function getLocations()
	{
		$locations = array();
    if(!$this->connected) return $locations;

    $result = $this->db->query('SELECT branch_id, branch_town, branch_address, branch_zip, branch_fax, branch_phone, branch_mail, brand_id, brand_short ' .
                                'FROM anm_branches WHERE brand_id > 0 ORDER BY branch_id ASC');
		while ($row = $result->fetch_object()) {
      $location = new StdClass();
      $location->id = $row->branch_id;
      $location->name = $row->branch_town;
      $location->address = $row->branch_address;
      $location->plz = $row->branch_zip;
      $location->fax = $row->branch_fax;
      $location->phone = $row->branch_phone;
      $location->email = $row->branch_mail;
      $location->brand_id = $row->brand_id;
      $location->brand_short = $row->brand_short;
			$locations[] = $location;
		}
		return $locations;
	}

	/**
	 * Gets the location (branch) by branch id
	 *
	 * @url GET /locations/$id
	 */
	public function getLocation($id)
	{
		$location = FALSE;
    if(!$this->connected) return $location;

    $id = intval($id);
		$result = $this->db->query('SELECT branch_id, branch_town, branch_address, branch_zip, branch_fax, branch_phone, branch_mail, brand_id, brand_short ' .
                                'FROM anm_branches WHERE brand_id > 0 AND branch_id = ' . $id);
		if ($row = $result->fetch_object()) {
      $location = new StdClass();
      $location->id = $row->branch_id;
      $location->name = $row->branch_town;
      $location->address = $row->branch_address;
      $location->plz = $row->branch_zip;
      $location->fax = $row->branch_fax;
      $location->phone = $row->branch_phone;
      $location->email = $row->branch_mail;
      $location->brand_id = $row->brand_id;
      $location->brand_short = $row->brand_short;
		}
		return $location;
	}
}