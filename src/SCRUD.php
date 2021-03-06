<?php

namespace BlackFox2;
/**
 * Class SCRUD -- Search, Create, Read, Update, Delete
 * @package BlackFox2
 *
 * Предоставляет функционал для работы с источниками данных [с таблицами в базе данных]:
 * - синхронизация структуры таблицы со структурой, описанной в классе-наследнике (включая создание таблицы)
 * - Search - постраничный поиск [+ выборка] записей в таблице
 * - Create - создание записей
 * - Read - чтение первой подходящей по фильтрам записи
 * - Update - обновление записей
 * - Delete - удаление указанных записей
 *
 * Чтобы создать новый источник данных нужно:
 * - создать класс-наследник от SCRUD
 * - переопределить метод Init, определить в нем структуру данных $this->structure
 * - однократно запустить $this->Synchronize(), например в установщике модуля
 * - при необходимости переопределить другие методы (например проверки целостности при создании или редактировании записи)
 * - при необходимости добавить дополнительный функционал, описывающий бизнес-логику работы с данными
 */
abstract class SCRUD {

	use Instance;

	/** @var string последний выполненный SQL-запрос (для отладки) */
	public $SQL;
	/** @var array части от последнего SQL-запроса (для отладки) */
	public $parts;
	/** @var Database коннектор базы данных */
	protected $DB;

	/** @var string имя источника данных или таблицы или сущностей в ней */
	public $name;
	/** @var string символьный код таблицы, формируется автоматически, возможно переопределить */
	public $code;

	/** @var Type[] массив полей базы данных */
	public $fields = [];
	/** @var array массив групп полей базы данных */
	public $groups = [];
	/** @var array композиция групп полей и полей базы данных, формируется автоматически на основе $this->structure и $this->groups */
	public $composition = [];
	/** @var array массив первичных ключей, формируется автоматически */
	public $keys = [];
	/** @var string код авто-прирастающего поля */
	public $increment = null;

	/**
	 * Идентификатор
	 */
	const ID = [
		'TYPE'           => 'INTEGER',
		// 'UNSIGNED'       => true, // todo UNSIGNED
		'NAME'           => 'ID',
		'INDEX'          => true,
		'PRIMARY'        => true,
		'NOT_NULL'       => true,
		'AUTO_INCREMENT' => true,
		'DISABLED'       => true,
		'VITAL'          => true,
	];


	public function __construct(Database $DB) {
		$this->DB = $DB;
		$this->code = strtolower(implode('_', array_filter(explode('\\', static::class))));
		$this->Init();
		$this->ProvideIntegrity();
	}

	/**
	 * Returns the only one single primary key if it exist.
	 * Otherwise throws exception.
	 *
	 * @return string
	 * @throws Exception Single primary key required
	 */
	public function key() {
		if (count($this->keys) === 1) {
			return reset($this->keys);
		}
		throw new Exception("Single primary key required for " . static::class);
	}

	/**
	 * Обеспечивает целостность данных между свойствами: structure, groups, composition, keys.
	 * - формирует keys перебором structure,
	 * - дополняет groups перебором structure,
	 * - формирует composition перебором groups и structure,
	 * - переопределяет structure объектами Type
	 */
	public function ProvideIntegrity() {

		foreach ($this->fields as $code => $info) {
			$info['CODE'] = $code;
			$this->fields[$code] = FactoryType::I()->Get($info, $this->DB);
		}

		$this->composition = [];
		$this->keys = [];

		foreach ($this->fields as $code => $field) {
			if ($field['PRIMARY']) {
				$this->keys[] = $code;
			}
			if ($field['AUTO_INCREMENT']) {
				$this->increment = $code;
			}
			if (empty($field['GROUP'])) {
				$this->fields[$code]['GROUP'] = 'OUTSIDE';
				$this->groups['OUTSIDE'] = $this->groups['OUTSIDE'] ?: '-';
				continue;
			}
			if (empty($this->groups[$field['GROUP']])) {
				$this->groups[$field['GROUP']] = "[{$field['GROUP']}]";
			}
		}

		foreach ($this->groups as $group_code => $group_name) {
			$this->composition[$group_code] = [
				'NAME'   => $group_name,
				'FIELDS' => [],
			];
			foreach ($this->fields as $code => &$field) {
				if ($field['GROUP'] === $group_code) {
					$this->composition[$group_code]['FIELDS'][$code] = &$field;
				}
			}
		}

		if (empty($this->keys)) {
			throw new Exception("Primary keys required for " . static::class);
		}

		// Auto-completion of LINK attributes without namespaces
		foreach ($this->fields as $code => &$info) {
			if (!empty($info['LINK']) && !class_exists($info['LINK'])) {
				$link_namespace = (new \ReflectionClass($this))->getNamespaceName();
				$link = $link_namespace . '\\' . $info['LINK'];
				if (class_exists($link)) {
					$info['LINK'] = $link;
				} else {
					throw new Exception("Valid class name required for LINK of field '{$code}' of class " . static::class);
				}
			}
		}
	}

	/**
	 * Инициализатор объекта, объявляется в классе-наследнике.
	 * Может использовать другие объекты для формирования структуры.
	 * Должен определить собственные поля: name, structure
	 * Может определить собственные поля: groups, code
	 */
	public function Init() {

	}

	public function DropConstraints() {
		$this->DB->DropTableConstraints($this->code);
	}

	public function Synchronize() {
		$this->DB->SynchronizeTable($this->code, $this->fields);
	}

	public function CreateConstraints() {
		$this->DB->CreateTableConstraints($this->code, $this->fields);
	}

	/**
	 * Формирует данные для вывода страницы элементов.
	 *
	 * $params - массив с ключами:
	 * - SORT -- сортировка, массив, ключ - поле, значение - ASC|DESC
	 * - FILTER -- пользовательский фильтр (можно передавать небезопасные данные)
	 * - CONDITIONS -- произвольные SQL условия для фильтрации (нельзя передавать небезопасные данные)
	 * - FIELDS -- выбираемые поля, составной массив
	 * - LIMIT -- количество элементов на странице (по умолчанию: *100*, нельзя не ограничивать выборку в этом методе, для неограниченной выборки используйте метод Select)
	 * - PAGE -- номер страницы (по умолчанию: *1*)
	 * - KEY -- по какому полю нумеровать элементы (по умолчанию: *$this->key()*, укажите *null* чтобы нумеровать по возрастанию)
	 * - ESCAPE -- автоматически обрабатывать поля с выбором формата text/html в HTML-безопасный вид? (по умолчанию: *true*)
	 * - GROUP -- группировка, лист, значение - код поля
	 *
	 * @param array $params
	 * @return array - ассоциативный массив с двумя ключами: ELEMENTS:[[]], PAGER:[TOTAL, CURRENT, LIMIT, SELECTED]
	 * @throws Exception
	 */
	public function Search($params = []) {
		$params['LIMIT'] = isset($params['LIMIT']) ? $params['LIMIT'] : 100;
		$params['PAGE'] = max(1, intval($params['PAGE']));

		$result['ELEMENTS'] = $this->Select($params);

		$result['PAGER']['CURRENT'] = $params['PAGE'];
		$result['PAGER']['LIMIT'] = $params['LIMIT'];
		$result['PAGER']['SELECTED'] = count($result['ELEMENTS']);

		$SQL_for_total = $this->DB->CompileSQLSelect([
			'SELECT' => ['COUNT(*) as total'],
			'TABLE'  => $this->parts['TABLE'],
			'JOIN'   => $this->parts['JOIN'],
			'WHERE'  => $this->parts['WHERE'],
		]);
		$this->SQL = [$this->SQL, $SQL_for_total];
		$result['PAGER']['TOTAL'] = (int)$this->DB->Query($SQL_for_total)[0]['total'];

		return $result;
	}

	/**
	 * Выбирает данные из таблицы
	 *
	 * $params - массив с ключами:
	 * - SORT -- сортировка, массив, ключ - поле, значение - ASC|DESC
	 * - FILTER -- пользовательский фильтр (можно передавать небезопасные данные)
	 * - CONDITIONS -- произвольные SQL условия для фильтрации (нельзя передавать небезопасные данные)
	 * - FIELDS -- выбираемые поля, составной массив
	 * - LIMIT -- количество элементов на странице (по умолчанию: *false*)
	 * - PAGE -- номер страницы (по умолчанию: *1*)
	 * - KEY -- по какому полю нумеровать элементы (по умолчанию: *$this->key()*, укажите *null* чтобы нумеровать по возрастанию)
	 * - ESCAPE -- автоматически обрабатывать поля с выбором формата text/html в HTML-безопасный вид? (по умолчанию: *true*)
	 * - GROUP -- группировка, лист, значение - код поля
	 *
	 * @param array $params
	 * @return array список выбранных элементов
	 * @throws Exception
	 */
	public function Select($params = []) {
		$this->_controlParams($params, ['KEY', 'SORT', 'FILTER', 'CONDITIONS', 'FIELDS', 'LIMIT', 'PAGE', 'ESCAPE', 'GROUP']);
		$defParams = [
			'SORT'       => [],
			'FILTER'     => [],
			'CONDITIONS' => [],
			'FIELDS'     => ['*@@'],
			'LIMIT'      => false,
			'PAGE'       => 1,
			'ESCAPE'     => true,
			'GROUP'      => [],
		];
		try {
			$defParams['KEY'] = $this->key();
			$defParams['SORT'] = [$this->key() => 'DESC'];
		} catch (Exception $error) {
			$defParams['KEY'] = null;
		}

		$params += $defParams;

		$params['FIELDS'] = $this->ExplainFields($params['FIELDS']);

		// если в полях нет ключевого поля - добавить его
		if (!empty($params['KEY']) and !in_array($params['KEY'], $params['FIELDS'])) {
			$params['FIELDS'][$params['KEY']] = $params['KEY'];
		}

		// parts of SQL
		$this->parts = [
			'TABLE'  => $this->code,
			'SELECT' => [],
			'JOIN'   => [],
			'WHERE'  => [],
			'ORDER'  => [],
			'GROUP'  => [],
			'LIMIT'  => [],
		];

		$answer = $this->PreparePartsByFields($params['FIELDS']);
		$this->parts['SELECT'] += $answer['SELECT'];
		$this->parts['JOIN'] += $answer['JOIN'];

		$answer = $this->PreparePartsByFilter($params['FILTER']);
		$this->parts['WHERE'] += $answer['WHERE'];
		$this->parts['JOIN'] += $answer['JOIN'];
		$this->parts['GROUP'] += $answer['GROUP'];

		$params['CONDITIONS'] = is_array($params['CONDITIONS']) ? $params['CONDITIONS'] : [$params['CONDITIONS']];
		$this->parts['WHERE'] += $params['CONDITIONS'];

		$answer = $this->PreparePartsByGroup($params['GROUP']);
		$this->parts['JOIN'] += $answer['JOIN'];
		$this->parts['GROUP'] += $answer['GROUP'];

		$answer = $this->PreparePartsBySort($params['SORT']);
		$this->parts['ORDER'] += $answer['ORDER'];
		$this->parts['JOIN'] += $answer['JOIN'];
		$this->parts['GROUP'] += $answer['GROUP'];
		$inner_sort = $answer['INNER_SORT'];

		if ($params['LIMIT'] > 0) {
			$this->parts['LIMIT'] = [
				'FROM'  => ($params['PAGE'] - 1) * $params['LIMIT'],
				'COUNT' => $params['LIMIT'],
			];
		}

		$this->SQL = $this->DB->CompileSQLSelect($this->parts);

		$elements = $this->Query($this->SQL, $params['KEY']);

		foreach ($elements as &$row) {
			$row = $this->FormatArrayKeysCase($row);
			$row = $this->FormatListStructure($row);
			$row = $this->FormatOutputValues($row);
			if ($params['ESCAPE']) {
				array_walk_recursive($row, function (&$value) {
					$value = htmlspecialchars($value);
				});
			}
		}

		$elements = $this->HookExternalFields($elements, $params['FIELDS'], $inner_sort);

		return $elements;
	}

	/**
	 * Контролирует соответствие массива параметров заданному листу допустимых параметров.
	 * В случае несоответствия кидает ошибку.
	 *
	 * @param array $params массив контролируемых параметров
	 * @param array $keys лист допустимых параметров
	 * @throws Exception Переданы некорректные параметры ...
	 */
	private function _controlParams($params = [], $keys = []) {
		$errors = [];
		foreach ($params as $key => $value) {
			if (!in_array($key, $keys)) {
				$errors[] = $key;
			}
		}
		if (!empty($errors)) {
			throw new Exception("Wrong params: [" . implode(", ", $errors) . "], allowed params: [" . implode(", ", $keys) . "]");
		}
	}

	/**
	 * Выбирает первый элемент по фильтру. Можно указать поля и сортировку.
	 *
	 * @param mixed $filter идентификатор | список идентификаторов | ассоциатив фильтров
	 * @param array|string $fields выбираемые поля
	 * @param array $sort сортировка
	 * @param bool $escape автоматически обрабатывать поля с выбором формата text/html в HTML-безопасный вид? (по умолчанию TRUE)
	 * @return array|false ассоциативный массив, представляющий собой элемент
	 * @throws Exception
	 */
	public function Read($filter, $fields = ['*@@'], $sort = [], $escape = true) {
		return reset($this->Select([
			'FILTER' => $filter,
			'FIELDS' => $fields,
			'SORT'   => $sort,
			'ESCAPE' => $escape,
			'LIMIT'  => 1,
		]));
	}

	/**
	 * Возвращает значение искомого поля первого элемента по фильтру.  Можно указать сортировку.
	 *
	 * @param mixed $filter идентификатор | список идентификаторов | ассоциатив фильтров
	 * @param string $field выбираемое поле
	 * @param array $sort сортировка
	 * @param bool $escape автоматически обрабатывать поля с выбором формата text/html в HTML-безопасный вид? (по умолчанию TRUE)
	 * @return mixed значение искомого поля
	 * @throws Exception
	 */
	public function GetCell($filter, $field, $sort = [], $escape = true) {
		return $this->Read($filter, [$field], $sort, $escape)[$field];
	}

	/**
	 * Проверяет присутствует ли элемент с указанным идентификатором в таблице
	 *
	 * @param mixed $filter идентификатор | список идентификаторов | ассоциатив фильтров
	 * @return boolean true - если присутствует, false - если не присутствует
	 * @throws Exception
	 */
	public function Present($filter) {
		return (bool)$this->Read($filter, $this->keys);
	}

	/**
	 * Выбирает список идентификаторов\значений указанной колонки.
	 *
	 * @param mixed $filter идентификатор | список идентификаторов | ассоциатив фильтров
	 * @param string $field символьный код выбираемой колонки (не обязательно, по умолчанию - идентификатор)
	 * @param array $sort сортировка (не обязательно)
	 * @param bool $escape
	 * @return array массив идентификаторов элементов
	 * @throws Exception
	 */
	public function GetColumn($filter = [], $field = null, $sort = [], $escape = false) {
		$field = $field ?: $this->key();
		$elements = $this->Select([
			'FILTER' => $filter,
			'FIELDS' => [$field],
			'SORT'   => $sort,
			'ESCAPE' => $escape,
		]);
		$rows = [];
		foreach ($elements as $key => $element) {
			$rows[$key] = $element[$field];
		}
		return $rows;
	}

	/**
	 * Вычисляет количество элементов, подходящих под фильтр.
	 *
	 * @param mixed $filter идентификатор | список идентификаторов | ассоциатив фильтров
	 * @return int
	 * @throws Exception
	 */
	public function Count($filter = []) {
		$answer = $this->PreparePartsByFilter($filter);
		$SQL = $this->DB->CompileSQLSelect([
			'SELECT' => ['COUNT(*) as total'],
			'TABLE'  => $this->code,
			'JOIN'   => $answer['JOIN'],
			'WHERE'  => $answer['WHERE'],
			// 'GROUP'  => $answer['GROUP'], // ???
		]);
		return (int)$this->DB->Query($SQL)[0]['total'];
	}

	/**
	 * Вычисляет сумму элементов, подходящих под фильтр.
	 *
	 * @param mixed $filter идентификатор | список идентификаторов | ассоциатив фильтров
	 * @param mixed $field колонка для подсчета суммы
	 * @return int
	 * @throws Exception
	 */
	public function Sum($filter = [], $field = '') {
		$answer = $this->PreparePartsByFilter($filter);
		$SQL = $this->DB->CompileSQLSelect([
			'SELECT' => ['SUM(' . $this->DB->Quote($field) . ') as total'],
			'TABLE'  => $this->code,
			'JOIN'   => $answer['JOIN'],
			'WHERE'  => $answer['WHERE'],
			// 'GROUP'  => $answer['GROUP'], // ???
		]);
		return (int)$this->DB->Query($SQL)[0]['total'];
	}

	/**
	 * Анализирует значение на наличие информации.
	 * - 0 - информация присутствует
	 * - 0.0 - информация присутствует
	 * - '0' - информация присутствует
	 * - false - информация присутствует
	 * - null - информация отсутствует
	 * - empty array() - информация отсутствует
	 * - '' - информация отсутствует
	 * - в других случаях - информация присутствует
	 *
	 * Отсутствие информации в переменных PHP эквивалетно в SQL значению NULL:
	 * PHP null == PHP empty array() == PHP '' == SQL NULL == SQL ''
	 *
	 * @param mixed $value значение
	 * @return boolean флаг наличия информации
	 */
	public function _hasInformation($value) {
		if ($value === 0 || $value === 0.0 || $value === '0' || $value === false) {
			return true;
		}
		if (empty($value)) {
			return false;
		}
		return true;
	}

	/**
	 * Формирует часть SQL запроса "SET ..., ..., ..." для вставки\изменения.
	 * Если значение пустая строка или null - возвращает "... = NULL".
	 * Если значение пустая строка или null, а поле NOT_NULL - ошибка.
	 *
	 * @param string $code код поля
	 * @param mixed $value значение поля
	 * @return string подстрока для SQL
	 * @throws Exception Поле ... не может быть пустым
	 */
	private function _prepareSet($code, $value) {
		$hasInformation = $this->_hasInformation($value);
		if (($this->fields[$code]['NOT_NULL'] || $this->fields[$code]['TYPE'] == 'BOOL') && !$hasInformation) {
			throw new Exception("Field '{$this->fields[$code]['NAME']}' can not be empty");
		}
		if ($hasInformation) {
			$value = $this->_formatFieldValue($code, $value);
			if (!is_null($value)) {
				$set = $this->DB->Quote($code) . " = '{$value}'";
			} else {
				$set = $this->DB->Quote($code) . " = NULL";
			}
		} else {
			$set = $this->DB->Quote($code) . " = NULL";
		}
		return $set;
	}

	/**
	 * Создает новые строки в таблице
	 *
	 * @param array $elements лист ассоциативных массивов полей для новых строк
	 * @throws Exception
	 */
	public function Insert($elements) {

		$errors = [];
		foreach ($elements as $element)
			foreach ($this->fields as $code => $field)
				if ($field['NOT_NULL'] && !$field['AUTO_INCREMENT'] && !isset($field['DEFAULT']))
					if (!$this->_hasInformation($element[$code]))
						$errors[] = "Field must be specified: '{$field['NAME']}'";
		if ($errors) throw new Exception($errors);

		$codes = [];
		foreach ($this->fields as $code => $field)
			if (!$field->virtual)
				$codes[] = $this->DB->Quote($code);

		$rows = [];
		foreach ($elements as $element) {
			$values = [];
			foreach ($this->fields as $code => $field) {
				if ($field->virtual) continue;
				if (array_key_exists($code, $element)) {
					$value = $this->_formatFieldValue($code, $element[$code]);
					$values[] = is_null($value) ? 'NULL' : "'{$value}'";
				} else {
					$values[] = 'DEFAULT';
				}
			}
			$rows[] = "\r\n" . '(' . implode(', ', $values) . ')';
		}

		$this->SQL = "INSERT INTO {$this->code} (" . implode(', ', $codes) . ") \r\n VALUES " . implode(', ', $rows);
		$this->DB->Query($this->SQL);
	}

	/**
	 * Создает новую строку в таблице и возвращает ее идентификатор
	 *
	 * @param array $element ассоциативный массив полей для новой строки
	 * @return int|string идентификатор созданной строки
	 * @throws Exception
	 */
	public function Create($element) {

		if (empty($element)) {
			$this->SQL = "INSERT INTO {$this->code} VALUES ()";
			return $this->DB->QuerySingleInsert($this->SQL, $this->increment);
		}

		$errors = [];
		foreach ($this->fields as $code => $field)
			if ($field['NOT_NULL'] && !$field['AUTO_INCREMENT'] && !isset($field['DEFAULT']))
				if (!$this->_hasInformation($element[$code]))
					$errors[] = "Field must be specified: '{$field['NAME']}'";
		if ($errors) throw new Exception($errors);

		$codes = [];
		$values = [];

		foreach ($this->fields as $code => $field) {
			if (array_key_exists($code, $element)) {
				$codes[] = $this->DB->Quote($code);
				$value = $this->_formatFieldValue($code, $element[$code]);
				$values[] = is_null($value) ? 'NULL' : "'{$value}'";
			}
		}

		$this->SQL = "INSERT INTO {$this->code} (" . implode(', ', $codes) . ") \r\n VALUES (" . implode(', ', $values) . ')';
		return $this->DB->QuerySingleInsert($this->SQL, $this->increment);
	}

	/**
	 * Изменяет значения указанных элементов.
	 *
	 * @param mixed $filter идентификатор | список идентификаторов | ассоциатив фильтров
	 * @param array $element ассоциативный массив изменяемых полей
	 * @throws Exception Нет информации для обновления
	 * @throws Exception Поле ... не может быть пустым
	 */
	public function Update($filter = [], $element = []) {

		if (empty($element)) {
			throw new Exception("No data to update");
		}

		$this->SQL = "UPDATE {$this->code} SET ";

		$rows = [];
		foreach ($this->fields as $code => $field) {
			if (array_key_exists($code, $element)) {
				$rows[] = $this->_prepareSet($code, $element[$code]);
			}
		}
		if (empty($rows)) {
			throw new Exception("No rows to update");
		}
		$this->SQL .= implode(",\r\n", $rows);

		$answer = $this->PreparePartsByFilter($filter);
		$this->SQL .= "\r\n WHERE " . implode(' AND ', $answer['WHERE']);

		$this->Query($this->SQL);
	}

	/**
	 * Удаляет строки из таблицы
	 *
	 * @param mixed $filter идентификатор | список идентификаторов | ассоциатив фильтров
	 * @throws Exception
	 * @throws ExceptionSQL
	 */
	public function Delete($filter = []) {
		$answer = $this->PreparePartsByFilter($filter);
		$this->SQL = "DELETE FROM {$this->code} WHERE " . implode(' AND ', $answer['WHERE']);
		$this->Query($this->SQL);
	}

	/**
	 * Создает массивы для выборки и джоинов
	 *
	 * @param array $fields поля для выборки
	 * @param string $prefix префикс
	 * @return array массив из двух элементов:
	 * - SELECT - []
	 * - JOIN - []
	 * @throws Exception
	 */
	public function PreparePartsByFields($fields, $prefix = "") {
		$select = [];
		$join = [];
		foreach ($fields as $code => $content) {
			if (!is_array($content)) {
				$code = strtoupper($content);
				$subfields = null;
			} else {
				$code = strtoupper($code);
				$subfields = $content;
			}
			unset($content);

			if (empty($this->fields[$code])) {
				throw new Exception("Unknown field code: '{$code}' in table '{$this->code}'");
			}
			$result = $this->fields[$code]->PrepareSelectAndJoinByField($this->code, $prefix, $subfields);
			$select += (array)$result['SELECT'];
			$join += (array)$result['JOIN'];
		}
		return [
			'SELECT' => $select,
			'JOIN'   => $join,
		];
	}

	/**
	 * Подготавливает часть SQL запроса WHERE из фильтра.
	 * Значения в фильтре могут быть:
	 * - *значение* - фильтр будет формироваться по всем правилам
	 * - 0 (zero) - фильтр будет формироваться по всем правилам
	 * - "" (empty string) - фильтр не будет формироваться
	 * - null - фильтр сформируется в "[NOT] IS NULL"
	 * - лист значений - сформируется набор ".. IN (...) [OR ... [NOT] IS NULL]"
	 *
	 * Ключи в фильтре могут быть:
	 * - <символьный код поля>
	 * - <символьный код поля типа ссылка>.<символьный код поля внешнего объекта>
	 * - <символьный код поля типа ссылка>.<символьный код поля типа ссылка>.<символьный код поля внешнего объекта>
	 * - ...
	 * - OR или AND
	 *
	 * Ключи поддерживают префиксы:
	 * -  =   - равно (по умолчанию)
	 * -  !   - не равно
	 * -  <>  - не равно
	 * -  >   - больше либо равно
	 * -  <   - меньше либо равно
	 * -  >>  - строго больше
	 * -  <<  - строго меньше
	 * -  ~   - LIKE
	 *
	 * @param mixed $filter ассоциатив фильтров | список идентификаторов | идентификатор
	 * @return array :
	 * - WHERE - массив строк, представляющих собой SQL-условия, которые следует объеденить операторами AND или OR
	 * - JOIN - ассоциативный массив уникальных SQL-строк, описывающий присоединяемые таблицы
	 * - GROUP - ассоциативный массив уникальных SQL-строк, описывающий группировку
	 * @throws Exception
	 */
	public function PreparePartsByFilter($filter) {
		if (!is_array($filter) and empty($filter)) {
			throw new Exception('Empty non-array filter passed (ID missed?)');
		}
		if (is_array($filter) and empty($filter)) {
			return [
				'WHERE' => [],
				'JOIN'  => [],
				'GROUP' => [],
			];
		}
		if (!is_array($filter)) {
			// filter is identifier
			$filter = [$this->key() => $filter];
		}
		// if array does not has string keys
		if (count(array_filter(array_keys($filter), 'is_string')) === 0) {
			// and if array does not has array values
			if (count(array_filter($filter, 'is_array')) === 0) {
				// filter is list of identifiers
				$filter = [$this->key() => $filter];
			}
		}

		$where = [];
		$join = [];
		$group = [];

		foreach ($filter as $filter_key => $values) {
			if ($values === '') {
				continue;
			}

			// вложенные операторы AND и OR
			if ($filter_key === 'AND' || $filter_key === 'OR' || is_numeric($filter_key)) {
				if (!is_array($values)) {
					throw new Exception('When using nested AND and OR operators in a filter, the value must be an array of conditions');
				}
				if (!is_numeric($filter_key)) {
					$logic = $filter_key;
				} else {
					$logic = $values['LOGIC'];
					if (!in_array($logic, ['AND', 'OR'])) {
						throw new Exception('When using a digital key in the filter, the array value must contain the key LOGIC = AND|OR');
					}
					unset($values['LOGIC']);
				}
				$answer = $this->PreparePartsByFilter($values);
				if (!empty($answer['WHERE'])) {
					$where[] = '(' . implode(" {$logic} ", $answer['WHERE']) . ')';
				}
				$join += $answer['JOIN'];
				$group += $answer['GROUP'];
				continue;
			}

			// вычисление ключа
			// possible $key : >>FIELD
			// possible $key : >>EXTERNAL_FIELD.FIELD
			// possible $key : >>EXTERNAL_FIELD.EXTERNAL_FIELD.FIELD
			// possible $key : ...
			preg_match('/^(?P<operator>\W*)(?P<field_path>[a-zA-Z_\.]+)/', $filter_key, $matches);
			$operator = $matches['operator']; // >>
			$field_path = $matches['field_path'];

			$result = $this->_treatFieldPath($field_path);
			/** @var self $Object */
			$Object = $result['OBJECT'];
			$table = $result['TABLE'];
			$code = $result['CODE'];
			$join += $result['JOIN'];
			$group += $result['GROUP'];

			if (empty($Object->fields[$code])) {
				throw new Exception("Can't form filter: unknown field '{$code}'");
			}
			$conditions = $Object->fields[$code]->PrepareConditions($table, $operator, $values);

			$conditions = "(" . implode(' OR ', $conditions) . ")";
			$where[] = $conditions;
		}
		return [
			'WHERE' => $where,
			'JOIN'  => $join,
			'GROUP' => $group,
		];
	}

	/**
	 * Обрабатывает путь к полю.
	 * Вычисляет объект для обработки поля, псевдоним таблицы и код поля.
	 * Возвращает структуру с ключами:
	 * - OBJECT - объект-наследник SCRUD для обработки поля
	 * - TABLE - псевдоним для таблицы (alias)
	 * - CODE - код поля
	 * - JOIN - ассоциативный массив уникальных SQL-строк, описывающий присоединяемые таблицы
	 * - GROUP - ассоциативный массив уникальных SQL-строк, описывающий группировку
	 *
	 * @param string $field_path мнемонический путь к полю, например: 'EXTERNAL_FIELD.EXTERNAL_FIELD.FIELD'
	 * @return array
	 * @throws Exception Unknown external field code
	 * @throws Exception Field is not external
	 */
	protected function _treatFieldPath($field_path) {
		$path = explode(".", $field_path); // EXTERNAL_FIELD.EXTERNAL_FIELD.FIELD
		$code = array_pop($path); // FIELD
		if (empty($path)) {
			return [
				'OBJECT' => $this,
				'TABLE'  => $this->code,
				'PATH'   => $path,
				'CODE'   => $code,
				'JOIN'   => [],
				'GROUP'  => [],
				'TYPE'   => [],
			];
		}
		// if (!empty($path)):
		/** @var self $Object */
		/** @var Type $Type */
		$Object = $this;
		$prefix = '';
		$join = [];
		$group = [];

		foreach ($path as $external) {
			$structure = &$Object->fields;
			$Info = $structure[$external];

			if (empty($Info)) {
				throw new Exception("Unknown external field code: '{$external}'");
			}
			if (empty($Info['LINK'])) {
				throw new Exception("Field is not external: '{$external}'");
			}

			$answer = $Info->GenerateJoinAndGroupStatements($Object, $prefix);
			$join += $answer['JOIN'];
			$group += $answer['GROUP'];

			// for next iteration
			$prefix .= $external . "__";
			$Object = $Info['LINK']::I();
		}
		return [
			'OBJECT' => $Object,
			'TABLE'  => $prefix . $Object->code,
			'PATH'   => $path,
			'CODE'   => $code,
			'JOIN'   => $join,
			'GROUP'  => $group,
		];
	}

	public function PreparePartsBySort($array) {
		$order = [];
		$join = [];
		$group = [];
		$inner_sort = [];
		foreach ($array as $field_path => $sort) {
			if ('{RANDOM}' === $field_path) {
				$order[] = $this->DB->Random();
				continue;
			}

			$result = $this->_treatFieldPath($field_path);

			if (count($result['PATH']) > 0) {
				$path = $result['PATH'];
				$first_path = array_shift($path);
				$FirstInfo = $this->fields[$first_path];
				if (is_a($FirstInfo, 'BlackFox2\TypeInner')) {
					$inner_path = implode('.', array_merge($path, [$result['CODE']]));
					$inner_sort[$first_path][$inner_path] = $sort;
					continue;
				}
			}

			$order[] = "{$result['TABLE']}." . $this->DB->Quote($result['CODE']) . " {$sort}";
			$join += $result['JOIN'];
			$group += $result['GROUP'];
		}

		return [
			'ORDER'      => $order,
			'JOIN'       => $join,
			'GROUP'      => $group,
			'INNER_SORT' => $inner_sort,
		];
	}

	/**
	 * Подготавливает часть SQL запроса GROUP BY
	 *
	 * @param array $array Массив фильтра GROUP
	 * @return array Массив с ключами GROUP BY
	 * @throws Exception
	 */
	public function PreparePartsByGroup($array) {
		$group = [];
		$join = [];
		foreach ($array as $field_path) {
			$result = $this->_treatFieldPath($field_path);
			$group[] = $this->DB->Quote($result['TABLE']) . '.' . $this->DB->Quote($result['CODE']);
			$join += $result['JOIN'];
		}
		return [
			'GROUP' => $group,
			'JOIN'  => $join,
		];
	}

	/**
	 * Приводит значение в соответствие формату поля.
	 * - Числовые - приводит к числу нужного формата.
	 * - Строковые - обрезает по нужному размеру.
	 * - Списковые - подставляет корректное значение.
	 * - Битовые - подставляет true|false.
	 * - Даты - подставляет дату в формате базы данных.
	 * - Файловые - сохраняет файл в BlackFox2\Files и выдает его идентификатор.
	 *
	 * @param string $code код поля
	 * @param mixed $value значение
	 * @return mixed приведенное к формату значение
	 * @throws Exception Unknown field code
	 */
	protected function _formatFieldValue($code, $value) {
		$code = strtoupper($code);
		if (!isset($this->fields[$code])) {
			throw new Exception("Unknown field code: '{$code}'");
		}

		if (!$this->_hasInformation($value)) {
			return null;
		}

		$value = $this->fields[$code]->FormatInputValue($value);

		return $this->DB->Escape($value);
	}


	/**
	 * Повышает регистр ключей массива на первом уровне вложенности
	 *
	 * @param array $input Входной массив
	 * @return array Выходной массив
	 */
	public function FormatArrayKeysCase($input) {
		return array_change_key_case($input, CASE_UPPER);
	}

	/**
	 * Возвращает экземпляр класса SCRUD, на который ссылается поле
	 *
	 * @param mixed $info массив, описывающий поле (с ключем LINK)
	 * @return SCRUD экземпляр
	 * @throws Exception
	 */
	private function GetLink($info) {
		if (!class_exists($info['LINK'])) {
			throw new Exception("You must set class name to LINK info of field '{$info['NAME']}'");
		}
		$parents = class_parents($info['LINK']);
		if (!in_array('BlackFox2\SCRUD', $parents)) {
			throw new Exception("You must set class (child of SCRUD) name to LINK info of field '{$info['NAME']}'");
		}
		/** @var SCRUD $Link */
		$Link = $info['LINK']::I();
		return $Link;
	}


	public function ExplainFields($fields) {
		$output = [];
		foreach ($fields as $key => $value) {
			if (is_numeric($key) and is_array($value)) {
				throw new Exception("{$this->name}->ExplainFields: Numeric key '{$key}' with array value");
			}
			$o_key = is_numeric($key) ? $value : $key;

			if (is_array($value)) {
				if (!isset($this->fields[$key])) {
					throw new Exception("{$this->name}->ExplainFields: Unknown field with code '{$key}'");
				}
				$output[$o_key] = $this->GetLink($this->fields[$key])->ExplainFields($value);
				continue;
			}

			// if (!is_array($value)):
			$first_symbol = substr($value, 0, 1);
			if (!in_array($first_symbol, ['*', '@'])) {
				$output[$o_key] = $value;
				continue;
			}

			// if (in_array($first_symbol, ['*', '@'])):
			$last_symbols = substr($value, 1);
			foreach ($this->fields as $code => $info) {
				if ($first_symbol === '@' and !$info['VITAL']) {
					continue;
				}
				if (!isset($info['LINK'])) {
					$output[$code] = $code;
					continue;
				}
				if (empty($last_symbols)) {
					$output[$code] = $code;
					continue;
				}

				$output[$code] = $this->GetLink($info)->ExplainFields([$last_symbols]);
				continue;
			}
		}
		return $output;
	}

	/**
	 * Преобразует одномерный ассоциативный массив с ключами типа ["EXTERNAL__NAME"]
	 * древовидный ассоциативный массив с ключами типа ["EXTERNAL"]["NAME"].
	 * Поддерживается неограниченная вложенность.
	 *
	 * @param array $list одномерный ассоциативный массив
	 * @param string $separator разделитель
	 * @return array древовидный ассоциативный массив
	 */
	public function FormatListStructure($list, $separator = "__") {
		$element = [];
		foreach ($list as $code => $value) {
			$codes = explode($separator, $code);
			$link = &$element;
			foreach ($codes as $path) {
				$link = &$link[$path];
			}
			$link = $value;
		}
		return $element;
	}

	/**
	 * Format output element values from database to user.
	 * No escape.
	 *
	 * @param array $element output element
	 * @return array output element with formatted values
	 * @throws Exception Unknown field code
	 */
	public function FormatOutputValues($element) {
		if (!is_array($element)) {
			return $element;
		}
		foreach ($element as $code => $value) {
			$info = $this->fields[$code];
			if (empty($info)) {
				throw new Exception("Unknown field code '{$code}'");
			}

			$element = $this->fields[$code]->FormatOutputValue($element);
		}
		return $element;
	}

	/**
	 * Удаляет все данные из таблицы не затрагивая структуру.
	 */
	public function Truncate() {
		$this->DB->Truncate($this->code);
	}

	/**
	 * Удаляет таблицу.
	 */
	public function Drop() {
		$this->DB->Drop($this->code);
	}

	/**
	 * Исполняет SQL-запрос, не останавливая выполнение в случае ошибки.
	 * Вместо этого кидает исключение (с текстом ошибки для администраторов).
	 *
	 * @param string $SQL SQL-запрос
	 * @param string $key код колонки значения которой будут использованы как ключ в результирующем массиве (не обязательно)
	 * @return array|int результат выполнения
	 * @throws ExceptionSQL
	 * @throws Exception
	 */
	public function Query($SQL, $key = null) {
		return $this->DB->Query($SQL, $key);
	}

	/**
	 * Для всех полей в выборке запускает метод HookExternalField.
	 * Это позволяет типу поля подцеплять внешние данные к элементам выборки (если это требуется).
	 * Например тип TypeInner подцепляет все внешние элементы, ссылающиеся на выбранные элементы.
	 *
	 * @param array $elements элементы выборки
	 * @param array $fields
	 * @param array $sort
	 * @return array элементы выборки, дополненные внешними данными
	 */
	private function HookExternalFields($elements, $fields, $sort) {
		if (empty($elements)) return $elements;
		foreach ($fields as $key => $value) {
			if (!is_array($value)) {
				$code = strtoupper($value);
				$subfields = null;
			} else {
				$code = strtoupper($key);
				$subfields = $value;
			}
			$subsort = $sort[$code] ?: [];
			$elements = $this->fields[$code]->HookExternalField($elements, $subfields, $subsort);
		}
		return $elements;
	}

}
