<?php

class MssqlSchema extends CMssqlSchema
{
    use SmartColumnTypeTrait {
        getColumnType as parentGetColumnType;
    }
    public function __construct($conn) {
        parent::__construct($conn);
        /**
         * Recommended practice.
         */
        $this->columnTypes['text'] = 'nvarchar(max)';
        /**
         * DbLib bugs if no explicit NOT NULL is specified.
         */
        $this->columnTypes['pk'] = 'int IDENTITY PRIMARY KEY NOT NULL';
        /**
         * Varchar cannot store unicode, nvarchar can.
         */
        $this->columnTypes['string'] = 'nvarchar(255)';
        /**
         * Auto increment.
         */
        $this->columnTypes['autoincrement'] = 'integer NOT NULL IDENTITY (1,1)';
        
        $this->columnTypes['longbinary'] = 'varbinary(max)';
    }

    public function getColumnType($type)
	{
        /**
         * @date 2015-5-11
         * Bug occurs with DBLIB when specifying neither of NULL and NOT NULL.
         * So if resulting type doesn't contain NULL then add it.
         */
        $result = $this->parentGetColumnType($type);
        
        if (stripos($result, 'NULL') === false) {
            $result .= ' NULL';
        }
        return $result;
    }
    
    // Original function calls getColumnType twice for unkown reasons
    public function alterColumn($table, $column, $type)
    {
        $sql='ALTER TABLE ' . $this->quoteTableName($table) . ' ALTER COLUMN '
            . $this->quoteColumnName($column) . ' '
            . $this->getColumnType($type);
        return $sql;
    }    
}