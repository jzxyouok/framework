<?php
namespace Leaps\Database\Query\Grammars;
use Leaps\Database\Query\Builder;
class SQLiteGrammar extends Grammar
{

    /**
     * Compile the "order by" portions of the query.
     *
     * @param \Leaps\Database\Query\Builder $query
     * @param array $orders
     * @return string
     */
    protected function compileOrders(Builder $query, $orders)
    {
        $me = $this;
        return 'order by ' . implode ( ', ', array_map ( function ($order) use($me)
        {
            return $me->wrap ( $order ['column'] ) . ' collate nocase ' . $order ['direction'];
        }, $orders ) );
    }

    /**
     * Compile an insert statement into SQL.
     *
     * @param \Leaps\Database\Query\Builder $query
     * @param array $values
     * @return string
     */
    public function compileInsert(Builder $query, array $values)
    {
        $table = $this->wrapTable ( $query->from );
        if ( ! is_array ( reset ( $values ) ) ) {
            $values = array (
                    $values
            );
        }
        if ( count ( $values ) == 1 ) {
            return parent::compileInsert ( $query, $values [0] );
        }
        $names = $this->columnize ( array_keys ( $values [0] ) );
        $columns = array ();
        foreach ( array_keys ( $values [0] ) as $column ) {
            $columns [] = '? as ' . $this->wrap ( $column );
        }
        $columns = array_fill ( 0, count ( $values ), implode ( ', ', $columns ) );
        return "insert into $table ($names) select " . implode ( ' union select ', $columns );
    }

    /**
     * Compile a truncate table statement into SQL.
     *
     * @param \Leaps\Database\Query\Builder $query
     * @return array
     */
    public function compileTruncate(Builder $query)
    {
        $sql = array (
                'delete from sqlite_sequence where name = ?' => array (
                        $query->from
                )
        );
        $sql ['delete from ' . $this->wrapTable ( $query->from )] = array ();
        return $sql;
    }
}
