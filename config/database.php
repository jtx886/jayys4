<?php
// 数据库连接类 - 使用PDO（兼容所有PHP版本）

require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private static $connectionError = null;
    private $pdo;

    private function __construct() {
        $errors = [];
        // ===== 按优先级尝试多种连接方式 =====
        $dsnCandidates = [];

        // 方式1：配置指定了Unix Socket
        if (defined('DB_SOCKET') && !empty(DB_SOCKET)) {
            $dsnCandidates[] = 'mysql:unix_socket=' . DB_SOCKET . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        }
        // 方式2：标准TCP带端口
        if (defined('DB_PORT') && !empty(DB_PORT)) {
            $dsnCandidates[] = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        }
        // 方式3：普通host（兼容老版本/部署环境）
        $dsnCandidates[] = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        // 方式4：localhost走默认socket
        if (DB_HOST === 'localhost') {
            $sockets = ['/run/mysqld/mysqld.sock', '/var/run/mysqld/mysqld.sock', '/tmp/mysql.sock'];
            foreach ($sockets as $sock) {
                if (file_exists($sock)) {
                    $dsnCandidates[] = 'mysql:unix_socket=' . $sock . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
                }
            }
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        foreach ($dsnCandidates as $idx => $dsn) {
            try {
                $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                self::$connectionError = null;
                return;
            } catch (PDOException $e) {
                $errors[] = "[方式$idx] " . $e->getMessage();
            }
        }

        // ===== 以上全部失败，再尝试不带dbname连接 =====
        try {
            $fallbackDsn = str_replace(';dbname=' . DB_NAME, '', $dsnCandidates[0]);
            $this->pdo = new PDO($fallbackDsn, DB_USER, DB_PASS, $options);
            self::$connectionError = null;
            return;
        } catch (PDOException $eLast) {
            $errors[] = "[仅主机连接] " . $eLast->getMessage();
        }

        self::$connectionError = implode(" ; ", $errors);
        $this->pdo = null;
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
        // 对$data的key加前缀:以避免命名冲突
        $params = [];
        foreach ($data as $k => $v) { $params[':' . $k] = $v; }
        $sql = 'INSERT INTO ' . $table . ' (' . implode(',', $fields) . ') VALUES (' . implode(',', $placeholders) . ')';
        $this->query($sql, $params);
        return $this->pdo->lastInsertId();
    }

    // 工具方法：将where中的?占位符和whereParams转换为命名参数
    private function normalizeWhereParams($where, $whereParams) {
        if (empty($whereParams)) return [$where, []];
        // 如果已经是命名参数(key带:)，直接返回
        $isNamed = false;
        foreach (array_keys($whereParams) as $k) {
            if (is_string($k) && strpos($k, ':') === 0) { $isNamed = true; break; }
        }
        if ($isNamed) return [$where, $whereParams];

        // 把?转换为:where_0, :where_1...
        $i = 0;
        $newParams = [];
        $newWhere = preg_replace_callback('/\?/', function($m) use (&$i, $whereParams, &$newParams) {
            $key = ':where_' . $i;
            $newParams[$key] = $whereParams[$i] ?? null;
            $i++;
            return $key;
        }, $where);
        return [$newWhere, $newParams];
    }

    public function update($table, $data, $where, $whereParams = []) {
        list($normalWhere, $normalWhereParams) = $this->normalizeWhereParams($where, $whereParams);
        $sets = [];
        $params = [];
        foreach ($data as $key => $value) {
            $sets[] = $key . ' = :set_' . $key;
            $params[':set_' . $key] = $value;
        }
        $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $sets) . ' WHERE ' . $normalWhere;
        $params = array_merge($params, $normalWhereParams);
        return $this->query($sql, $params)->rowCount();
    }

    public function delete($table, $where, $whereParams = []) {
        list($normalWhere, $normalWhereParams) = $this->normalizeWhereParams($where, $whereParams);
        $sql = 'DELETE FROM ' . $table . ' WHERE ' . $normalWhere;
        return $this->query($sql, $normalWhereParams)->rowCount();
    }
}
