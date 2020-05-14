[Конструктор](#конструктор-запросов)

## Подключение
```
require_once '{PATH_TO_DOCUMENT_ROOT}/functions/class_db.php';
```
### Переход со старого на новый код

##### Вместо конструкций типа 
```
$query = "SELECT * FROM ta_manager WHERE id = " . mysql_real_escape_string($id) . " ORDER BY por, id";
$result = mysql_query($query);
while ($row = mysql_fetch_assoc($result)) {
    $manager = $row;
}
```
##### Используем следующий код
```
$query = "SELECT * FROM `ta_manager` WHERE `id` = :id ORDER BY por, id";
$data = DB::query($query, [':id' => $id])->fetchAll();
```

...

## Использование
#### Простой запрос
```
bool DB::query($query);
```
Возвращает результат `true` если запрос выполнен, `false` - если запрос ничего не вернул.

В случае ошибки выбрасывается исключение и выводится скрипт и строка ошибки и описание ошибки.

#### Количество обработанных запросом строк
`
$count = DB::query($query)->rowCount();
`

#### Подготовленные выражения (prepared statements)
> Использование prepared statements укрепляет защиту от SQL-инъекций.

Prepared statement — это заранее скомпилированное SQL-выражение, которое может быть многократно выполнено путем отправки серверу лишь различных наборов данных. Дополнительным преимуществом является невозможность провести SQL-инъекцию через данные, используемые в placeholder’ах.

##### Безымянные placeholder’ы (?)
```
$query = "SELECT `articul`, `name` FROM `ta_menu` WHERE `id` = ? AND `menu` = ?";
$params = [100, 'Y'];
$data = DB::query($query, params)->fetch();
```
##### Именные placeholder’ы (:placeholder_name)

```
$query = "SELECT `articul`, `name` FROM `ta_menu` WHERE `id` = :id AND `menu` = :menu";
$params = [
    ':id' => 100,
    ':menu' => 'Y',
];
$data = DB::query($query, params)->fetch();
```

#### Выборка результатов запроса SELECT

Выбор одной строки *fetch()*

```
$query = "SELECT `articul` FROM `ta_menu` WHERE `id` = :id";
$articul = DB::query($query, [':id' => 2797])->fetch(); // если не указан PDO::FETCH_NUM то вернется ассоциативный массив
```
Возвращает
```
[
    [id] => 2797
    [name] => Плащ влагозащитный (зеленый)
]
// или если в fetch(PDO::FETCH_NUM) то вернется нумерованный массив
[
    [0] => 2797
    [1] => Плащ влагозащитный (зеленый)
]
```

Выбор всех результатов запроса *fetchAll()*

```
$data = DB::query($query)->fetchAll();
```

##### Выбор варианта возвращаемых индексов FETCH_STYLE

Выборка ассоциативного массива данных

```
$data = DB::query($query)->fetchAll(PDO::FETCH_ASSOC);
```
Возвращает
```
[
    [0] => Array
        (
            [id] => 2797
            [name] => Плащ влагозащитный (зеленый)
        )
    [1] => Array
        (
            [id] => 2180
            [name] => Чулки вкладные «Полизон»
        )
    [2] => Array
        (
            [id] => 1686
            [name] => Очки «Ультравижн» (9301813) со сменными пленками
        )
]
```

Выборка нумерованного массива данных
```
$data = DB::query($query)->fetchAll(PDO::FETCH_NUM);
```
Возвращает
```
[
    [0] => Array
        (
            [0] => 2797
            [1] => Плащ влагозащитный (зеленый)
        )
    [1] => Array
        (
            [0] => 2180
            [1] => Чулки вкладные «Полизон»
        )
    [2] => Array
        (
            [0] => 1686
            [1] => Очки «Ультравижн» (9301813) со сменными пленками
        )
]
```

##### Проверка результатов выборки

```
$query = "SELECT `id`, `name` FROM `ta_menu` WHERE `id` = ?";
if (!$array = DB::query($query, [$id])->fetchAll()) {
    echo "Нет записей";
}
```

```
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

```
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

```
INSERT INTO 
    `ta_cart` 
SET 
    `signature` = :signature, 
    `cart` = :cart 
ON DUPLICATE KEY UPDATE 
    `cart` = VALUES(`cart`)
```

#### Получение последнего вставленного в таблицу индекса LAST_INSERT_ID
```
$lid = DB::instance()->lastInsertId();
```

## Конструктор запросов

#### Пример

```
$menu = DB::table('ta_menu')
    ->fields('ta_menu.id, ta_menu.articul, ta_menu.name, ta_menu_parent.name as parent')
    ->limit(2, 2)
    ->order('ta_menu.id');

$menu->join('ta_menu', 'ta_menu_parent.id', 'ta_menu.parent_id', null, 'ta_menu_parent');
$menu->where('ta_menu.articul', '', '<>');
$products = $menu->exec();
```

#### table($table) // таблица

```
$query = DB::table('ta_menu');
```

#### fields($fields) // назначаем поля таблицы

```
$query->fields('ta_menu.id, ta_menu.articul, ta_menu.name, ta_menu_parent.name as parent');
```

#### join($table, $field1, $field2, $condition = ' = ', $alias = '')

```
$query->join('ta_menu', 'ta_menu_parent.id', 'ta_menu.parent_id', null, 'ta_menu_parent');
```

#### innerJoin($table, $field1, $field2, $condition = ' = ', $alias = '')

Аналогичен `join`.

#### where($field, $value, $condition = ' = ', $combine_condition = 'AND')

```
$query->where('ta_menu.articul', '', '<>');
```

#### whereRaw($where)

```
$query->whereRaw('id = :id');
```

#### order($field, $direction = 'ASC')

```
$query->order('id');
```

#### limit($limit, $from = '0')

```
$query->limit(10);
```

#### exec()

```
$products = $query->exec();
```
