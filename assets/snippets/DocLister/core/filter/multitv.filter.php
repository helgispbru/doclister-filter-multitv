<?php
require_once 'tv.filter.php';

/**
 * Filters DocLister results by value of a given Evo CMS multiTV.
 * @author helgispbru <helgispbru@gmail.com>
 */
class multitv_DL_filter extends tv_DL_filter
{
    /**
     * Конструктор условий для WHERE секции
     *
     * @param string $table_alias алиас таблицы
     * @param string $field поле для фильтрации
     * @param string $operator оператор сопоставления
     * @param string $value искомое значение
     * @return string
     */
    protected function build_sql_where($table_alias, $field, $operator, $value)
    {
        $this->DocLister->debug->debug(
            'Build SQL query for filters: ' . $this->DocLister->debug->dumpData(func_get_args()),
            'buildQuery',
            2
        );
        $output = '';

        $delimiter = $this->DocLister->getCFGDef('filter_delimiter', ',');

        $value = explode('|', $this->value);
        // название поля
        $name = $value[0];
        //  значение поля
        $val = $value[1] ?? '';

        switch ($operator) {
            // точное совпадение
            case '=':
            case 'eq':
            case 'is':
                // multitv:tvname:is:fieldname|fieldvalue
                $val = $this->modx->db->escape($val);
                $output .= " JSON_SEARCH(" . $table_alias . ".value, 'one', '" . $val . "', NULL, '$.fieldValue[*]." . $name . "') IS NOT NULL";
                break;

            // нет совпадений
            case '!=':
            case 'no':
            case 'isnot':
                // multitv:tvname:isnot:fieldname|fieldvalue
                $val = $this->modx->db->escape($val);
                $output .= "JSON_SEARCH(" . $table_alias . ".value, 'one', '" . $val . "', NULL, '$.fieldValue[*]." . $name . "') IS NULL";
                break;

            // нет никакого значения
            case 'isnull':
                // multitv:tvname:isnull:fieldname|fieldvalue
                $output .= "JSON_EXTRACT(" . $table_alias . ".value, '$.fieldValue[*]." . $name . "') IS NULL";
                break;

            // есть какое-то значение
            case 'isnotnull':
                // multitv:tvname:isnotnull:fieldname|fieldvalue
                $output .= "JSON_EXTRACT(" . $table_alias . ".value, '$.fieldValue[*]." . $name . "') IS NOT NULL";
                break;

            // есть значение больше
            case '>':
            case 'gt':
                // multitv:tvname:gt:fieldname|fieldvalue
                $val = str_replace(',', '.', floatval($val));
                $output .= "EXISTS (
                    SELECT 1
                    FROM JSON_TABLE(" . $table_alias . ".value, '$.fieldValue[*]' COLUMNS (" . $name . " VARCHAR(255) PATH '$." . $name . "')) AS jt
                    WHERE " . $table_alias . ".`tmplvarid` = " . $this->tv_id . " AND CAST(jt." . $name . " AS FLOAT) > " . $val . "
                )";
                break;

            // меньше
            case '<':
            case 'lt':
                // multitv:tvname:lt:fieldname|fieldvalue
                $val = str_replace(',', '.', floatval($val));
                $output .= "EXISTS (
                    SELECT 1
                    FROM JSON_TABLE(" . $table_alias . ".value, '$.fieldValue[*]' COLUMNS (" . $name . " VARCHAR(255) PATH '$." . $name . "')) AS jt
                    WHERE " . $table_alias . ".`tmplvarid` = " . $this->tv_id . " AND CAST(jt." . $name . " AS FLOAT) < " . $val . "
                )";
                break;

            // меньше равно
            case '<=':
            case 'elt':
                // multitv:tvname:elt:fieldname|fieldvalue
                $val = str_replace(',', '.', floatval($val));
                $output .= "EXISTS (
                    SELECT 1
                    FROM JSON_TABLE(" . $table_alias . ".value, '$.fieldValue[*]' COLUMNS (" . $name . " VARCHAR(255) PATH '$." . $name . "')) AS jt
                    WHERE " . $table_alias . ".`tmplvarid` = " . $this->tv_id . " AND CAST(jt." . $name . " AS FLOAT) <= " . $val . "
                )";
                break;

            // больше равно
            case '>=':
            case 'egt':
                // multitv:tvname:egt:fieldname|fieldvalue
                $val = str_replace(',', '.', floatval($val));
                $output .= "EXISTS (
                    SELECT 1
                    FROM JSON_TABLE(" . $table_alias . ".value, '$.fieldValue[*]' COLUMNS (" . $name . " VARCHAR(255) PATH '$." . $name . "')) AS jt
                    WHERE " . $table_alias . ".`tmplvarid` = " . $this->tv_id . " AND CAST(jt." . $name . " AS FLOAT) >= " . $val . "
                )";
                break;

            // по вхождению строки
            case '%':
            case 'like':
                // multitv:tvname:like:fieldname|fieldvalue
                $val = $this->modx->db->escape($val);
                $output .= "JSON_SEARCH(" . $table_alias . ".value, 'one', '%" . $val . "%', NULL, '$.fieldValue[*]." . $name . "') IS NOT NULL";
                break;

            // по началу строки
            case 'like-r':
                // multitv:tvname:like-r:fieldname|fieldvalue
                $val = $this->modx->db->escape($val);
                $output .= "JSON_SEARCH(" . $table_alias . ".value, 'one', '" . $val . "%', NULL, '$.fieldValue[*]." . $name . "') IS NOT NULL";
                break;

            // по окончанию строки
            case 'like-l':
                // multitv:tvname:like-l:fieldname|fieldvalue
                $val = $this->modx->db->escape($val);
                $output .= "JSON_SEARCH(" . $table_alias . ".value, 'one', '%" . $val . "', NULL, '$.fieldValue[*]." . $name . "') IS NOT NULL";
                break;

            // содержит хотя бы одно значение как часть строки
            case 'containsOne':
                // multitv:tvname:containsOne:field|val1,val2,val3
                $containsOneDelimiter = $this->DocLister->getCFGDef('filter_delimiter:containsOne', $delimiter);
                $words = explode($containsOneDelimiter, $val);
                $arr = [];
                foreach ($words as $word) {
                    // $word без trim, т.к. мало ли, вдруг важно найти не просто слово, а именно начало
                    $arr[] = "JSON_SEARCH(" . $table_alias . ".value, 'one', '%" . $word . "%', NULL, '$.fieldValue[*]." . $name . "') IS NOT NULL";
                }
                $output .= implode(' OR ', $arr);
                break;

            // содержит все значения как часть строки
            case 'containsAll':
                // multitv:tvname:containsAll:field|val1,val2,val3
                $containsAllDelimiter = $this->DocLister->getCFGDef('filter_delimiter:containsAll', $delimiter);
                $words = explode($containsAllDelimiter, $val);
                $arr = [];
                foreach ($words as $word) {
                    // $word без trim, т.к. мало ли, вдруг важно найти не просто слово, а именно его начало
                    $arr[] = "JSON_SEARCH(" . $table_alias . ".value, 'one', '%" . $word . "%', NULL, '$.fieldValue[*]." . $name . "') IS NOT NULL";
                }
                $output .= implode(' AND ', $arr);
                break;

            // значение строки равно одному из значений
            case 'in':
                // multitv:tvname:in:field|val1,val2,val3
                $inDelimiter = $this->DocLister->getCFGDef('filter_delimiter:in', $delimiter);
                $words = explode($inDelimiter, $val);
                $arr = [];
                foreach ($words as $word) {
                    $arr[] = "JSON_SEARCH(" . $table_alias . ".value, 'one', '" . $word . "', NULL, '$.fieldValue[*]." . $name . "') IS NOT NULL";
                }
                $output .= implode(' OR ', $arr);
                break;

            // значение строки не равно ни одному из значений
            case 'notin':
                // multitv:tvname:notin:field|val1,val2,val3
                $notinDelimiter = $this->DocLister->getCFGDef('filter_delimiter:notin', $delimiter);
                $words = explode($notinDelimiter, $val);
                $arr = [];
                foreach ($words as $word) {
                    $arr[] = "JSON_SEARCH(" . $table_alias . ".value, 'one', '" . $word . "', NULL, '$.fieldValue[*]." . $name . "') IS NULL";
                }
                $output .= implode(' AND ', $arr);
                break;

            default:
                $output = '';
        }

        $this->DocLister->debug->debugEnd("buildQuery");

        return $output;
    }
}
