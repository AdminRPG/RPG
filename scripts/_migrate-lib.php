<?php
if (!function_exists('col_exists')) {
    function col_exists($db, $table, $column) {
        $result = $db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
        return $result && $result->num_rows > 0;
    }
}

if (!function_exists('add_col')) {
    function add_col($db, $table, $column, $definition) {
        if (!col_exists($db, $table, $column)) {
            $db->query("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
            echo "  Added column {$column} to {$table}\n";
        } else {
            echo "  Column {$column} already exists in {$table}\n";
        }
    }
}

if (!function_exists('table_exists')) {
    function table_exists($db, $table) {
        $result = $db->query("SHOW TABLES LIKE '{$table}'");
        return $result && $result->num_rows > 0;
    }
}

if (!function_exists('run')) {
    function run($db, $callback) {
        try {
            $callback($db);
            echo "OK\n";
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
        }
    }
}
