<?php

namespace BlackFox2;

class Test_SCRUD_Links extends Test {

	public $name = 'SCRUD: one-many, many-one, many-many links';

	/** @var Grades $Grades */
	public $Grades;
	/** @var Students $Students */
	public $Students;
	/** @var Rooms $Rooms */
	public $Rooms;
	/** @var Timetable $Timetable */
	public $Timetable;

	/** @var Schema $Schema */
	public $Schema;

	public function __construct() {
		parent::__construct();
		$this->Grades = Grades::N();
		$this->Rooms = Rooms::N();
		$this->Timetable = Timetable::N();
		$this->Students = Students::N();

		$this->Schema = Schema::N();
		$this->Schema->SetTables([
			$this->Grades,
			$this->Rooms,
			$this->Timetable,
			$this->Students,
		]);
	}

	/** Schema drop */
	public function TestSchemaDrop() {
		$this->Schema->Drop();
	}

	/** Schema synchronize */
	public function TestSchemaSynchronize() {
		$this->Schema->Synchronize();
	}

	public function TestFillRooms() {
		$rooms = [101, 102, 103, 104, 105, 106, 107, 201, 203, 205, 207, 209, 301, 304, 307, 311];
		foreach ($rooms as $room) {
			$this->Rooms->Create(['TITLE' => 'R-' . $room]);
		}
	}

	public function TestFillGrades() {
		foreach (['A', 'B', 'C'] as $class_letter) {
			foreach ([1, 2, 3, 4, 5, 7, 8, 9, 10, 11] as $class_number) {
				$this->Grades->Create([
					'TITLE' => $class_number . $class_letter,
				]);
			}
		}
	}

	public function TestFillStudents() {
		$names = file(__DIR__ . '/data/names.txt', FILE_IGNORE_NEW_LINES);
		$lasts = ['J', 'G', 'V', 'X', 'Z'];
		$grade_ids = $this->Grades->GetColumn();
		if (empty($grade_ids)) {
			throw new Exception("No grades has been found");
		}
		for ($i = 0; $i < 100; $i++) {
			$this->Students->Create([
				'FIRST_NAME' => $names[array_rand($names)],
				'LAST_NAME'  => $lasts[array_rand($lasts)] . '.',
				'GRADE'      => $grade_ids[array_rand($grade_ids)],
			]);
		}
	}

	public function TestFillGradesCaptains() {
		$grade_ids = $this->Grades->GetColumn();
		$students_ids = $this->Students->GetColumn();
		foreach ($grade_ids as $grade_id) {
			$this->Grades->Update($grade_id, ['CAPTAIN' => $students_ids[array_rand($students_ids)]]);
		}
	}

	public function TestFillTimetable() {
		$grade_ids = $this->Grades->GetColumn();
		$rooms_ids = $this->Rooms->GetColumn();
		for ($i = 0; $i < 300; $i++) {
			$this->Timetable->Create([
				'GRADE' => $grade_ids[array_rand($grade_ids)],
				'ROOM'  => $rooms_ids[array_rand($rooms_ids)],
				'START' => time() + $i * 3600,
			]);
		}
	}

	/** Test of reading a random element (student), checking the structure */
	public function TestReadStudent() {
		$student_ids = $this->Students->GetColumn();
		$student = $this->Students->Read($student_ids[array_rand($student_ids)], ['*@']);
		if (!is_array($student)) {
			throw new Exception('$student is not array');
		}
		if (array_keys($student) <> ['ID', 'FIRST_NAME', 'LAST_NAME', 'GRADE']) {
			throw new Exception(['Wrong structure of $student', $student]);
		}
		if (array_keys($student['GRADE']) <> ['ID', 'TITLE']) {
			throw new Exception(['Wrong structure of $student[CLASS]', $student['GRADE']]);
		}
		//return $student;
	}

	/** Test of reading a random element (grade), checking the structure */
	public function TestReadGrade() {
		$grade_ids = $this->Grades->GetColumn();
		$grade = $this->Grades->Read($grade_ids[array_rand($grade_ids)], ['*@']);
		if (!is_array($grade)) {
			throw new Exception('$grade is not array');
		}
		if (array_keys($grade) <> ['ID', 'TITLE', 'CAPTAIN', 'STUDENTS', 'TIMETABLES']) {
			throw new Exception(['Wrong structure of $grade', $grade]);
		}
		if (empty($grade['STUDENTS'])) {
			return $this->TestReadGrade();
		}
		if (array_keys(reset($grade['STUDENTS'])) <> ['ID', 'FIRST_NAME']) {
			print_r($grade);
			throw new Exception(['Wrong structure of $grade->STUDENTS', array_keys(reset($grade['STUDENTS']))]);
		}
		//return $grade;
	}

	/** Test of reading a random element with a specific selection  */
	public function TestReadGradeStudents1A() {
		$grade_ids = $this->Grades->GetColumn();
		$grade = $this->Grades->Read($grade_ids[array_rand($grade_ids)], [
			'ID',
			'TITLE',
			'STUDENTS' => ['*'],
		]);
		if (!is_array($grade)) {
			throw new Exception('$grade is not array');
		}
		if (array_keys($grade) <> ['ID', 'TITLE', 'STUDENTS']) {
			throw new Exception(['Wrong structure of $grade', $grade]);
		}
		if (empty($grade['STUDENTS'])) {
			return $this->TestReadGradeStudents1A();
		}
		if (array_keys(reset($grade['STUDENTS'])) <> ['ID', 'FIRST_NAME', 'LAST_NAME']) {
			throw new Exception(['Wrong structure of $grade->STUDENTS', reset($grade['STUDENTS'])]);
		}
		//return $grade;
	}

	/** Test filter via chain: direct link */
	public function TestFilterStudentsByGrades() {
		$students = $this->Students->Select([
			'FILTER' => ['GRADE.TITLE' => '9B'],
		]);
		foreach ($students as $student) {
			if ($student['GRADE']['TITLE'] <> '9B') {
				throw new Exception(['Wrong student', $student]);
			}
		}
		//return $students;
	}

	/** Test filter via chain: direct link (there are no filter fields in the selection) */
	public function TestFilterStudentsByGradesWithoutFilterField() {
		$students = $this->Students->Select([
			'FILTER' => ['GRADE.TITLE' => '9B'],
			'FIELDS' => ['ID', 'FIRST_NAME'],
		]);
		foreach ($students as $student) {
			$data = $this->Students->Read($student['ID'], ['GRADE' => ['TITLE']]);
			if ($data['GRADE']['TITLE'] <> '9B') {
				throw new Exception(['Wrong student', $student]);
			}
		}
		// return $students;
	}

	/** Test filter via chain: direct link, direct link */
	public function TestFilterStudentsByGradeCaptain() {
		// Carlota
		$students = $this->Students->Select([
			'FILTER' => ['GRADE.CAPTAIN.FIRST_NAME' => 'Carlota'],
			'FIELDS' => ['ID', 'FIRST_NAME'],
		]);
		foreach ($students as $student) {
			$data = $this->Students->Read($student['ID'], ['GRADE' => ['CAPTAIN' => ['*']]]);
			if ($data['GRADE']['CAPTAIN']['FIRST_NAME'] <> 'Carlota') {
				throw new Exception(['Wrong student', $student]);
			}
		}
		// return $students;
	}

	/** Test filter via chain: inverse link */
	public function TestFilterGradesByStudents() {
		$grade_ids = $this->Grades->GetColumn();
		$random_grade = $this->Grades->Read($grade_ids[array_rand($grade_ids)], ['*@'], [], false);
		if (empty($random_grade['STUDENTS'])) {
			return $this->TestFilterGradesByStudents();
		}
		$random_student_first_name = $random_grade['STUDENTS'][array_rand($random_grade['STUDENTS'])]['FIRST_NAME'];

		$grades = $this->Grades->Select([
			'FIELDS' => ['ID', 'TITLE', 'STUDENTS' => ['@']],
			'FILTER' => ['STUDENTS.FIRST_NAME' => $random_student_first_name],
		]);
		foreach ($grades as $grade) {
			foreach ($grade['STUDENTS'] as $student) {
				if ($student['FIRST_NAME'] === $random_student_first_name) {
					continue 2;
				}
			}
			throw new Exception(['Wrong grade', $random_student_first_name, $grade]);
		}
		// return $grades;
	}

	/** Test filter via chain: inverse link + search by substring */
	public function TestFilterGradesByStudentsLike() {
		foreach (['ani', 'nia', 'vel'] as $search) {
			$grades = $this->Grades->Select([
				'FIELDS' => ['ID', 'TITLE', 'STUDENTS' => ['@']],
				'FILTER' => ['~STUDENTS.FIRST_NAME' => $search],
			]);
			foreach ($grades as $grade) {
				foreach ($grade['STUDENTS'] as $student) {
					if (!(stripos($student['FIRST_NAME'], $search) === false)) {
						continue 2;
					}
				}
				throw new Exception(['Wrong grade', $search, $grade]);
			}
		}
	}

	/** Test filter via chain: inverse link, direct link */
	public function TestFilterGradesByRooms() {
		$grades = $this->Grades->Select([
			'FIELDS' => ['ID', 'TITLE', 'TIMETABLES' => ['*@']],
			'FILTER' => ['TIMETABLES.ROOM.TITLE' => 'R-304'],
		]);
		foreach ($grades as $grade) {
			foreach ($grade['TIMETABLES'] as $timetable) {
				if ($timetable['ROOM']['TITLE'] === 'R-304') {
					continue 2;
				}
			}
			throw new Exception(['Wrong grade', $grade]);
		}
		// return $grades;
	}

	/** Foreign key test: RESTRICT creation */
	public function TestForeignRestrict() {
		$max_grade_id = $this->Grades->GetCell([], 'ID', ['ID' => 'DESC']);

		$new_student_id = $this->Students->Create([
			'FIRST_NAME' => 'New',
			'LAST_NAME'  => 'Student',
			'GRADE'      => $max_grade_id,
		]);
		if (empty($new_student_id)) {
			throw new Exception("Can't create new student");
		}

		try {
			$this->Students->Create([
				'FIRST_NAME' => 'Another',
				'LAST_NAME'  => 'Student',
				'GRADE'      => $max_grade_id + 1,
			]);
		} catch (\Exception $error) {
			return;
		}

		throw new Exception("Can create student with no-existing grade #" . ($max_grade_id + 1));
	}

	/** Foreign key test: CASCADE deletion */
	public function TestForeignCascade() {
		$timetable_ids = $this->Timetable->GetColumn();
		$random_timetable = $this->Timetable->Read($timetable_ids[array_rand($timetable_ids)], ['*']);
		$this->Rooms->Delete($random_timetable['ROOM']);
		$test = $this->Timetable->Read($random_timetable['ID']);
		if (!empty($test)) {
			throw new Exception(["Timetable still exist", $random_timetable, $test]);
		}
	}
}