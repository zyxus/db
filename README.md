Небольшой класс оберкта для PDO. Был разработан для служебных нужд, 
с целью облегчения перехода на PDO.
Конструктор запросов расширяет функционал использования PDO.

## Установка
Установка через [Composer](https://getcomposer.org).
```sh
$ composer require zyxus/db
```

## Оглавление

* [Использование](#Использование)
    * [::query()](#query)
    * [rowCount()](#rowCount)
    * [fetch()](#fetch)
    * [fetchAll()](#fetchAll)
    * [lastInsertId()](#lastInsertId)

* [Подготовленные выражения (prepared statements)](#prepared-statements)
    * [Безымянные placeholder’ы (?)](#placeholder)
    * [Именные placeholder’ы (:placeholder_name)](#placeholder_name)
    * [Выбор варианта возвращаемых индексов FETCH_STYLE](#fetch_style)
    * [Проверка результатов запроса](#Проверка-результатов-запроса)
    * [Вставка строки в таблицу INSERT](#insert)
    * [Синтаксис INSERT ON DUPLICATE KEY UPDATE](#on_duplicate_key)
     
* [Конструктор запросов](#конструктор-запросов)
    * [table](#table)
    * [fields](#fields)
    * [join](#join)
    * [innerJoin](#innerJoin)
    * [where](#where)
    * [whereRaw](#whereRaw)
    * [order](#order)
    * [limit](#limit)
    * [exec](#exec)

## Использование

### <a id="query"></a>DB::query()
Возвращает результат `true` - если запрос выполнен, `false` - если запрос ничего не вернул.
В случае ошибки выбрасывается исключение и выводится скрипт и строка ошибки и описание ошибки.
```php
bool DB::query($query);
```
### <a id="rowCount"></a>rowCount()
Возвращает количество обработанных запросом строк
```php
$count = DB::query($query)->rowCount();
```
### <a id="fetch"></a>fetch()
Возвращает 1 строку с результатом запроса
```php
$count = DB::query($query)->fetch();
```
### <a id="fetchAll"></a>fetchAll()
Возвращает все строки с результатами запроса
```php
$count = DB::query($query)->fetch();
```
### <a id="lastInsertId"></a>lastInsertId()
Возвращает последний вставленный запросом id 
```php
$count = DB::instance()->lastInsertId();
```

## <a id="prepared-statements"></a>Подготовленные выражения (prepared statements)
> Использование prepared statements укрепляет защиту от SQL-инъекций.

Prepared statement — это заранее скомпилированное SQL-выражение, которое может быть многократно выполнено путем отправки серверу лишь различных наборов данных. Дополнительным преимуществом является невозможность провести SQL-инъекцию через данные, используемые в placeholder’ах.

### <a id="placeholder"></a>Безымянные placeholder’ы (?)
```php
$query = "SELECT `field1`, `field2` FROM `table` WHERE `id` = ? AND `field3` = ?";
$params = [100, 'Y'];
$data = DB::query($query, params)->fetch();
```
### <a id="placeholder_name"></a>Именные placeholder’ы (:placeholder_name)

```php
$query = "SELECT `field1`, `field2` FROM `table` WHERE `id` = :id AND `field3` = :field3";
$params = [
    ':id' => 100,
    ':field3' => 'Y',
];
$data = DB::query($query, params)->fetch();
```

### Выборка результатов запроса SELECT

Выбор одной строки *fetch()*

```php
$query = "SELECT `field` FROM `table` WHERE `id` = :id";
$articul = DB::query($query, [':id' => 2797])->fetch(); // если не указан PDO::FETCH_NUM то вернется ассоциативный массив
```
Возвращает
```php
[
    [id] => 1234
    [name] => Название поля
]
// или если в fetch(PDO::FETCH_NUM) то вернется нумерованный массив
[
    [0] => 1234
    [1] => Название поля
]
```

Выбор всех результатов запроса *fetchAll()*

```php
$data = DB::query($query)->fetchAll();
```

### <a id="fetch_style"></a>Выбор варианта возвращаемых индексов FETCH_STYLE

Выборка ассоциативного массива данных

```php
$data = DB::query($query)->fetchAll(PDO::FETCH_ASSOC);
```
Возвращает
```php
[
    [0] => Array
        (
            [id] => 1234
            [name] => Название поля 1
        )
    [1] => Array
        (
            [id] => 1235
            [name] => Название поля 2
        )
    [2] => Array
        (
            [id] => 1236
            [name] => Название поля 3
        )
]
```

Выборка нумерованного массива данных
```php
$data = DB::query($query)->fetchAll(PDO::FETCH_NUM);
```
Возвращает
```php
[
    [0] => Array
        (
            [0] => 1234
            [1] => Название поля 1
        )
    [1] => Array
        (
            [0] => 1235
            [1] => Название поля 2
        )
    [2] => Array
        (
            [0] => 1236
            [1] => Название поля 3
        )
]
```

### Проверка результатов запроса

```php
$query = "SELECT `id`, `name` FROM `table` WHERE `id` = ?";
if (!$array = DB::query($query, [$id])->fetchAll()) {
    echo "Нет записей";
}
```

```php
$query = "SELECT `name` FROM `table` WHERE `id` = ?";
$result = DB::query($query, [$articul]);

if ($result->rowCount() > 0) {

    $row = $result->fetch();
    echo $row['name'];

} else {

    echo "Empty results";
}
```

### <a id="insert"></a>Вставка строки в таблицу INSERT

```php
$query = "
    INSERT INTO `table` (
        `id`,
        `title`
    ) VALUES (
        NULL,
        :title
    );
";
$params = array(
    ':title' => $title,
);
DB::query($query, $params);
```

### <a id="on_duplicate_key"></a>Синтаксис INSERT ON DUPLICATE KEY UPDATE

```php
INSERT INTO 
    `table` 
SET 
    `field1` = :field1, 
    `field2` = :field2 
ON DUPLICATE KEY UPDATE 
    `field2` = VALUES(`field2`)
```

## Конструктор запросов

#### Пример
```php
$menu = DB::table('table')
    ->fields('table.id, table.name, table_parent.name as parent')
    ->limit(2, 2)
    ->order('table.id');

$menu->join('table', 'table_parent.id', 'table.parent_id', null, 'table_parent');
$menu->where('table.articul', '', '<>');
$products = $menu->exec();
```

### <a id="table"></a>table($table)
Установка таблицы
```php
$query = DB::table('table');
```
### <a id="fields"></a>fields($fields)
Назначаем поля таблицы
```php
$query->fields('table.field1, table.field2');
```
### <a id="join"></a>join($table, $field1, $field2, $condition = ' = ', $alias = '')
```php
$query->join('table', 'table.id', 'table.parent_id', null, 'table_alias');
```
### <a id="innerJoin"></a>innerJoin($table, $field1, $field2, $condition = ' = ', $alias = '')
Аналогичен `join`.
### <a id="where"></a>where($field, $value, $condition = ' = ', $combine_condition = 'AND')
```php
$query->where('table.title', '', '<>');
```
### <a id="whereRaw"></a>whereRaw($where)
```php
$query->whereRaw('id = :id');
```
### <a id="order"></a>order($field, $direction = 'ASC')
Сортировка по полю `$field` с направлением `$direction`
```php
$query->order('id');
```
### <a id="limit"></a>limit($limit, $from = '0')
Выбор `$limit` записей начиная от `$from`
```php
$query->limit(10);
```
### <a id="exec"></a>exec()
Выполнение подготовленного запроса
```php
$products = $query->exec();
```

