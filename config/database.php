<?php
// 数据库连接类 - 使用PDO（兼容所有PHP版本）

require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private static $connectionError = null;
    private $pdo;

    private function __construct() {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            self::$connectionError = null;
        } catch (PDOException $e) {
            // 数据库不存在时尝试连接仅主机
            try {
                $dsn = 'mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET;
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ];
                $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                self::$connectionError = null;
            } catch (PDOException $e2) {
                self::$connectionError = $e2->getMessage();
                $this->pdo = null;
            }
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        if (self::$instance->pdo === null) {
            throw new Exception('数据库连接失败: ' . self::$connectionError);
        }
        return self::$instance;
    }

    public static function isConnected() {
        if (self::$instance === null) {
            try {
                self::$instance = new self();
            } catch (Exception $e) {}
        }
        return self::$instance !== null && self::$instance->pdo !== null;
    }

    public static function getConnectionError() {
        return self::$connectionError;
    }

    public function getConnection() {
        return $this->pdo;
    }

    // 便捷方法 - 全部添加异常抛出
    public function query($sql, $params = []) {
        if ($this->pdo === null) {
            throw new Exception('无可用数据库连接');
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetchOne($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }

    public function insert($table, $data) {
        $fields = array_keys($data);
        $placeholders = array_map(function($f) { return ':' . $f; }, $fields);
        $sql = 'INSERT INTO ' . $table . ' (' . implode(',', $fields) . ') VALUES (' . implode(',', $placeholders) . ')';
        $this->query($sql, $data);
        return $this->pdo->lastInsertId();
    }

    public function update($table, $data, $where, $whereParams = []) {
        $sets = [];
        $params = [];
        foreach ($data as $key => $value) {
            $sets[] = $key . ' = :set_' . $key;
            $params[':set_' . $key] = $value;
        }
        $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $sets) . ' WHERE ' . $where;
        $params = array_merge($params, $whereParams);
        return $this->query($sql, $params)->rowCount();
    }

    public function delete($table, $where, $whereParams = []) {
        $sql = 'DELETE FROM ' . $table . ' WHERE ' . $where;
        return $this->query($sql, $whereParams)->rowCount();
    }
}
