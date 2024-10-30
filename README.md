# doclister-filter-multitv

Фильтр для `DocLister` по TV типа `multiTV`, формат записи фильтра:

```
multitv:tvname:operator:name|value
```

Описание фильтра:

| название   | описание                    |
| ---------- | --------------------------- |
| `multitv`  | фильтр по `multiTV`         |
| `tvname`   | название `tv`               |
| `operator` | как сравнивать              |
| `name`     | поле `name` для поиска      |
| `value`    | значение `value` для поиска |

## Операторы

| Оператор            | Описание                                        |
| ------------------- | ----------------------------------------------- |
| `=`, `eq`, `is`     | равно, точное совпадение                        |
| `!=`, `no`, `isnot` | нет совпадений                                  |
| `isnull`            | нет никакого значения                           |
| `isnotnull`         | есть какое-тор значение                         |
| `>`, `gt`           | больше                                          |
| `<`, `lt`           | меньше                                          |
| `<=`, `elt`         | меньше или равно                                |
| `>=`, `egt`         | больше или равно                                |
| `%`, `like`         | содержит строку                                 |
| `like-r`            | начинается строкой                              |
| `like-l`            | заканчивается строкой                           |
| `сontainsOne`       | содержит хотя бы одно значение как часть строки |
| `сontainsAll`       | содержит все значения как часть строки          |
| `in`                | значение строки равно одному из значений        |
| `notin`             | значение строки не равно ни одному из значений  |

## Особенности по операторам

Хранение значений в `multiTV` сделано в виде массива объектов, а значения полей всегда хранится как строка, например для поля `name = 10` хранится в json как `"name": "10"`, а всё вместе выглядит так:

```
{
    "fieldValue": [
        { "name": "value1" },
        { "name": "value2" },
        { "name": "value3" }
    ],
    "fieldSettings": {
        "autoincrement": 1
    }
}
```

Из этого выходит ряд особенностей при поиске:

### Операторы `=`, `eq`, `is`, `!=`, `no`, `isnot`

_Формат:_ `multitv:tvname:is:fieldname|fieldvalue`

Ищет, что в массиве по полю `fieldname` есть хотя бы одно значение `fieldvalue` по указанным правилам сравнения.

Значения сравниваются как строки, то есть `10` и `10.0` это разные значения.

Например `multitv:price:is:100` не найдёт ничего, если значение указано как `100.0` и наоборот.

### Операторы `isnull`, `isnotnull`

_Формат:_ `multitv:tvname:isnull:fieldname|fieldvalue`

Проверяется отсутствие любого значения у `fieldname` для `isnull` или наличие хоть какого-то значения у `fieldname` для `isnotnull`.

### Операторы `>`, `gt`, `<`, `lt`, `<=`, `elt`, `>=`, `egt`

_Формат:_ `multitv:tvname:gt:fieldname|fieldvalue`

Ищет, что в массиве по полю `fieldname` есть хотя бы одно значение `fieldvalue` и подходит под сравнение как число.

При поиске значения в базе и в фильтре приводятся к `float`.

### Операторы `%`, `like`, `like-r`, `like-l`

Поиск по правилам `LIKE` в MySQL.

_Формат:_ `multitv:tvname:like:fieldname|fieldvalue`

Ищет, что в массиве по полю `fieldname` есть строка `fieldvalue`, сопадающая по правилам `LIKE`.

### Операторы `сontainsOne`, `сontainsAll`

_Формат:_ `multitv:tvname:containsOne:fieldname|val1,val2,val3`

-   `сontainsOne` содержит хотя бы одно значение как часть строки
-   `сontainsAll` содержит все значения как часть строки

Ищет, что в массиве по полю `fieldname` содержится строка, совпадающая по правилам `LIKE` для выбранного оператора.

### Операторы

_Формат:_ `multitv:tvname:in:field|val1,val2,val3`

-   `in` значение строки равно одному из значений
-   `notin` значение строки не равно ни одному из значений

Ищет, что в массиве по полю `fieldname` содержится строка, совпадающая для выбранного оператора.

## Пример

Например, есть `tv` типа `multiTV` под названием `product_code`, в которой содержится массив из нескольких `code`.

Тогда значение для `multiTV` будет иметь вид:

```
{
    "fieldValue": [
        { "code": "value1" },
        { "code": "value2" },
        { "code": "value3" }
    ],
    "fieldSettings": {
        "autoincrement": 1
    }
}
```

Найти все ресурсы, у которых в `tv` названной `product_code` в поле `code` есть `value2`:

```
multitv:product_code:is:code|value2
```
