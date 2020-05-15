Небольшой класс оберкта для PDO. Был разработан для служебных нужд, 
с целью облегчения перехода на PDO.
Конструктор запросов расширяет функционал использования PDO.

## Установка
Установка через [Composer](https://getcomposer.org).
```sh
$ composer require zyxus/db
```

Конструктор запросов

* Использование
    * [::query()](#DB::query())
    * [rowCount()](#rowCount())
    * [fetch()](#fetch())
    * [fetchAll()](#fetchAll())
    * [lastInsertId()](#lastInsertId())

* [Подготовленные выражения (prepared statements)](#Подготовленные-выражения-(prepared-statements))
* [Конструктор запросов](#конструктор-запросов)
    * [table](#table($table))
    * [fields](#fields($fields))
    * [join](#join)
    * [innerJoin](#innerJoin)
    * [where](#where)
    * [whereRaw](#whereRaw)
    * [order](#order)
    * [limit](#limit)
    * [exec](#exec)

## Использование

#### DB::query()
Возвращает результат `true` - если запрос выполнен, `false` - если запрос ничего не вернул.
В случае ошибки выбрасывается исключение и выводится скрипт и строка ошибки и описание ошибки.
```php
bool DB::query($query);
```
#### rowCount()
Возвращает количество обработанных запросом строк
```php
$count = DB::query($query)->rowCount();
```
#### fetch()
Возвращает 1 строку с результатом запроса
```php
$count = DB::query($query)->fetch();
```
#### fetchAll()
Возвращает все строки с результатами запроса
```php
$count = DB::query($query)->fetch();
```
#### lastInsertId()
Возвращает последний вставленный запросом id 
```php
$count = DB::instance()->lastInsertId();
```

#### Подготовленные выражения (prepared statements)
> Использование prepared statements укрепляет защиту от SQL-инъекций.

Prepared statement — это заранее скомпилированное SQL-выражение, которое может быть многократно выполнено путем отправки серверу лишь различных наборов данных. Дополнительным преимуществом является невозможность провести SQL-инъекцию через данные, используемые в placeholder’ах.

##### Безымянные placeholder’ы (?)
```php
$query = "SELECT `articul`, `name` FROM `ta_menu` WHERE `id` = ? AND `menu` = ?";
$params = [100, 'Y'];
$data = DB::query($query, params)->fetch();
```
##### Именные placeholder’ы (:placeholder_name)

```php
$query = "SELECT `articul`, `name` FROM `ta_menu` WHERE `id` = :id AND `menu` = :menu";
$params = [
    ':id' => 100,
    ':menu' => 'Y',
];
$data = DB::query($query, params)->fetch();
```

#### Выборка результатов запроса SELECT

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

##### Выбор варианта возвращаемых индексов FETCH_STYLE

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

##### Проверка результатов выборки

```php
$query = "SELECT `id`, `name` FROM `ta_menu` WHERE `id` = ?";
if (!$array = DB::query($query, [$id])->fetchAll()) {
    echo "Нет записей";
}
```

```php
$query = "SELECT `name` FROM `ta_menu` WHERE `articul` = ?";
$result = DB::query($query, [$articul]);

if ($result->rowCount() > 0) {

    $row = $result->fetch();
    echo $row['name'];

} else {

    echo "Empty results";
}
```


#### Вставка строки в таблицу INSERT

```php
$query = "
    INSERT INTO `ta_menu` (
        `id`,
        `menu_name`
    ) VALUES (
        NULL,
        :menu_name
    );
";
$params = array(
    ':menu_name' => $menu_name,
);
DB::query($query, $params);
```

##### Синтаксис INSERT ON DUPLICATE KEY UPDATE

```php
INSERT INTO 
    `ta_cart` 
SET 
    `signature` = :signature, 
    `cart` = :cart 
ON DUPLICATE KEY UPDATE 
    `cart` = VALUES(`cart`)
```

#### Получение последнего вставленного в таблицу индекса LAST_INSERT_ID
```php
$lid = DB::instance()->lastInsertId();
```

## Конструктор запросов

#### Пример

```php
$menu = DB::table('ta_menu')
    ->fields('ta_menu.id, ta_menu.articul, ta_menu.name, ta_menu_parent.name as parent')
    ->limit(2, 2)
    ->order('ta_menu.id');

$menu->join('ta_menu', 'ta_menu_parent.id', 'ta_menu.parent_id', null, 'ta_menu_parent');
$menu->where('ta_menu.articul', '', '<>');
$products = $menu->exec();
```

#### table($table)
Установка таблицы
```php
$query = DB::table('ta_menu');
```
#### fields($fields)
Назначаем поля таблицы
```php
$query->fields('ta_menu.id, ta_menu.articul, ta_menu.name, ta_menu_parent.name as parent');
```
#### <a id="join"></a>join($table, $field1, $field2, $condition = ' = ', $alias = '')[#join]
```php
$query->join('ta_menu', 'ta_menu_parent.id', 'ta_menu.parent_id', null, 'ta_menu_parent');
```
#### <a id="innerJoin"></a>innerJoin($table, $field1, $field2, $condition = ' = ', $alias = '')
Аналогичен `join`.
#### <a id="where"></a>where($field, $value, $condition = ' = ', $combine_condition = 'AND')
```php
$query->where('ta_menu.articul', '', '<>');
```
#### <a id="whereRaw"></a>whereRaw($where)
```php
$query->whereRaw('id = :id');
```
#### <a id="order"></a>order($field, $direction = 'ASC')
Сортировка по полю `$field` с направлением `$direction`
```php
$query->order('id');
```
#### <a id="limit"></a>limit($limit, $from = '0')
Выбор `$limit` записей начиная от `$from`
```php
$query->limit(10);
```
#### <a id="exec"></a>exec()
Выполнение подготовленного запроса
```php
$products = $query->exec();
```

