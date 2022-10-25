<?php
$servername = "localhost";
$username = "root";
$password = "";
$database  = "testdatabase";

/* 2 2.2-2.4

CREATE DATABASE testdatabase


CREATE TABLE patient (
id INT(10) UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY,
pn VARCHAR(11) DEFAULT NULL,     
first VARCHAR(15) DEFAULT NULL,  
last VARCHAR(25) DEFAULT NULL,
dob DATE DEFAULT NULL
)

CREATE TABLE insurance (
id INT(10) UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY,
patient_id INT(10) UNSIGNED NOT NULL, 
FOREIGN KEY patientid(patient_id)
REFERENCES patient(id)
ON UPDATE CASCADE
ON DELETE RESTRICT,
iname VARCHAR(40) DEFAULT NULL,
from_date DATE DEFAULT NULL,
to_date DATE DEFAULT NULL
)

INSERT INTO patient(`pn`, `first`, `last`, `dob`)
VALUES ('000000001', 'John', 'Doe', '22-10-20'),
('000000002', 'John', 'Smith', '22-10-20'),
('000000003', 'Paul', 'Tamm', '22-10-20'),
('000000004', 'Smith', 'Doe', '22-10-20'),
('000000005', 'Tamm', 'Smith', '22-10-20');

INSERT INTO insurance(`patient_id`, `iname`, `from_date`, `to_date`)
VALUES 
('1', 'Mediacre', '22-05-15', '23-09-20'),
('1', 'Blue Cross', '22-07-10', '23-11-20'),

('2', 'Mediacre', '22-06-11', '23-05-20'),
('2', 'Blue Cross', '22-08-20', '23-06-10'),

('3', 'Mediacre', '22-05-20', '23-04-20'),
('3', 'Blue Cross', '22-09-20', '23-05-15'),

('4', 'Mediacre', '22-01-20', '23-05-15'),
('4', 'Blue Cross', '22-03-25', '23-06-20'),

('5', 'Mediacre', '22-04-13', '23-02-15'),
('5', 'Blue Cross', '22-09-05', '23-03-20');

*/

// 3.2 a)
$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT pn, last, first, iname, from_date, DATE_FORMAT(from_date, '%m-%d-%y') AS format_from_date, to_date, DATE_FORMAT(to_date, '%m-%d-%y') AS format_to_date FROM patient INNER JOIN insurance ON patient.id=insurance.patient_id ORDER BY from_date ASC, last DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    print_r("\n" . $row["pn"] . ", " . $row["last"] . ", " . $row["first"] .  ", "  . $row["iname"] . ", " . $row["format_from_date"] .  ", " . $row["format_to_date"] .  "\n");
  }
} else {
  echo "0 results";
}
$conn->close();


// 3.2 B)
$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT first, last FROM patient";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
  $data = array();
  $count = 0;
  while ($row = $result->fetch_assoc()) {
    $fullname = strtoupper($row["first"] . $row["last"]);
    $count += strlen($fullname);
    for ($i = 0; $i < strlen($fullname); $i++) {
      if (array_key_exists($fullname[$i], $data)) {
        $data[$fullname[$i]]++;
      } else {
        $data[$fullname[$i]] = 1;
      }
    }
  }
  foreach ($data as $key => $value) {
    print_r("$key \t $value \t" . "\t" . round(($value / $count) * 100, 2) . " %\n");
  }
} else {
  echo "0 results";
}
$conn->close();

//4.1
interface PatientRecord
{
  public function get_id();
  public function get_pn();
}

//4.2
class Patient implements PatientRecord
{
  public $id;
  public $pn;
  public $first;
  public $last;
  public $dob;
  public $ins_rec;

  public function __construct($pn)
  {
    $servername = "localhost";
    $username = "root";
    $password = "test";
    $database  = "testdatabase";
    $conn = new mysqli($servername, $username, $password, $database);
    $pquery = "SELECT id, pn, first, last, dob FROM patient WHERE pn = '$pn'";
    $presult = $conn->query($pquery);
    $patient_row = $presult->fetch_assoc();
    $iquery = "SELECT id, patient_id, iname, from_date, to_date FROM insurance WHERE patient_id = " . $patient_row["id"];
    $iresult = $conn->query($iquery);
    $this->ins_rec = array();
    while ($row = $iresult->fetch_assoc()) {
      $insurance = new Insurance($row["id"], $row["patient_id"], $row["iname"], date_create_from_format("Y-m-d", $row["from_date"]), date_create_from_format("Y-m-d", $row["to_date"]));
      array_push($this->ins_rec, $insurance);
    }
    $this->id = $patient_row["id"];
    $this->pn = $pn;
    $this->first = $patient_row["first"];
    $this->last = $patient_row["last"];
    $this->dob = $patient_row["dob"];
    $conn->close();
  }

  public function get_id()
  {
    return $this->id;
  }

  public function get_pn()
  {
    return $this->pn;
  }

  public function getpatient_name()
  {
    return "$this->first $this->last";
  }

  public function getinsurancearray()
  {
    return $this->ins_rec;
  }

  public function get_insurancesfromdate(datetime $date)
  {
    $today = date_create_from_format("Y-m-d", date("Y-m-d"));
    $arr = array();
    foreach ($this->ins_rec as $rec) {
      if ($date >= $rec->from_date && $date <= $rec->to_date) {
        $valid = ($today >= $rec->from_date && $today <= $rec->to_date) ? "Yes" : "No";
        print_r($this->get_pn() . ", " . $this->getpatient_name() . ", " . $rec->iname . ", " . $valid . "\n");
        array_push($arr, $rec);
      }
    }
    return $arr;
  }
}

//4.3
class Insurance implements PatientRecord
{
  public $id;
  public $patient_id;
  public $iname;
  public $from_date;
  public $to_date;

  public function __construct($id, $patient_id, $iname, $from_date, $to_date)
  {
    $this->id = $id;
    $this->patient_id = $patient_id;
    $this->iname = $iname;
    $this->from_date = $from_date;
    $this->to_date = $to_date;
  }

  public function get_id()
  {
    return $this->id;
  }

  public function get_pn()
  {
    return $this->patient_id;
  }

  public function is_active_insurance($date)
  {
    return ($date >= $this->from_date && $date <= $this->to_date) ? "true" : "false";
  }
}

function testscript()
{
  $servername = "localhost";
  $username = "root";
  $password = "test";
  $database  = "testdatabase";
  $conn = new mysqli($servername, $username, $password, $database);
  $tquery = "SELECT pn FROM patient ORDER BY pn ASC";
  $tresult = $conn->query($tquery);
  while ($row = $tresult->fetch_assoc()) {
    $patient = new Patient($row["pn"]);
    $today = date_create_from_format("Y-m-d", date("Y-m-d"));
    $patient->get_insurancesfromdate($today);
  }
  $conn->close();
}


/* 4.2 6)
$patient = new Patient("000000002");
$date = date_create_from_format("Y-m-d", "2022-08-21");
$ins_arr = $patient->get_insurancesfromdate($date);
*/

/* 4.3 4)
$date = date_create_from_format("Y-m-d", "2022-08-21");
$patient = new Patient("000000002");
$ins_arr = $patient->get_insurancesfromdate($date);
$today = date_create_from_format("Y-m-d", date("Y-m-d"));
foreach ($ins_arr as $rec) {
  print_r($rec->is_active_insurance($today));
}
*/

/* 4.4 test script
  testscript();
*/
