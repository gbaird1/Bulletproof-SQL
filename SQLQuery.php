<?php
/**
 * SQLQuery.php - Bulletproof SQL Abstraction Layer
 *
 * A simple, secure, and pragmatic database abstraction class built on PHP mysqli.
 * Features prepared statements, an escape hatch for complex queries, lockdown mode,
 * and 15+ years of battlefield testing.
 *
 * @author      Gregory Baird (A3Ω)
 * @copyright   2026 Gregory Baird
 * @license     MIT
 * @version     1.0.0
 * @link        https://github.com/gbaird1/Bulletproof-SQL/
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

// Your existing SQLQuery class code below...

class SQLQuery {
    private mysqli $link;
    private array $errors = [];
    private bool $debug = true;
    private array $msg = [];
    private bool $lockdown = false;
    private bool $noint = false;

    public function __construct() {}

    public function connect(string $sqlhost, string $sqluser, string $sqlpass, string $sqlname): bool {
        $this->link = new mysqli($sqlhost, $sqluser, $sqlpass, $sqlname);

		$this->host = $sqlhost;
		$this->name = $sqluser;
		$this->pass = $sqlpass;
		$this->db_name = $sqlname;

        if ($this->link->connect_error) {
            $this->setError("Connection failed: " . $this->link->connect_error);
            return false;
        }

        return true;
    }

    public function close(): void {
        if (isset($this->link)) {
            $this->link->close();
        }
    }

    private function setError(string $error): void {
        $this->errors[] = $error;
    }

    public function getError(): ?string {
        return end($this->errors) ?: null;
    }

    private function executeQuery(string $query, array $params = []): bool|mysqli_result {
    if (!isset($this->link)) {
        $this->setError("No active database connection.");
        die("Error: No active database connection.");
    }

    $stmt = $this->link->prepare($query);
    if (!$stmt) {
        $this->setError("Query preparation failed: " . $this->link->error);
        die("Error: Query preparation failed: " . $this->link->error);
    }

    if ($params) {
        $types = str_repeat("s", count($params)); 
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        $this->setError("Query execution failed: " . $stmt->error);
        die("Error: Query execution failed: " . $stmt->error);
    }

    // If the query is SELECT, return the result
    if (str_starts_with(strtoupper($query), 'SELECT')) {
        return $stmt->get_result();
    }

    // For INSERT, UPDATE, DELETE, return true for success
    return true;
}

    public function select(string $table, array $conditions = [], string $sort = "", string $limit = "", string $columns = "*"): array {
        $query = "SELECT $columns FROM $table";
        $params = [];

        if (!empty($conditions)) {
            $whereClauses = [];
            foreach ($conditions as $key => $value) {
                $whereClauses[] = "$key = ?";
                $params[] = $value;
            }
            $query .= " WHERE " . implode(" AND ", $whereClauses);
        }

        if (!empty($sort)) {
            $query .= " ORDER BY $sort";
        }

        if (!empty($limit)) {
            $query .= " LIMIT $limit";
        }

        $result = $this->executeQuery($query, $params);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    
	public function select2( $query ) {
		//$this->debug( $query );
        $params = [];
        $result = $this->executeQuery( $query, $params );
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
	}

    public function insert(string $table, array $data): ?int {
        if ($this->lockdown) return null;

        $columns = implode(", ", array_keys($data));
        $placeholders = implode(", ", array_fill(0, count($data), "?"));
        $query = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        
        $params = array_values($data);
        $this->executeQuery($query, $params);
        return $this->link->insert_id;
    }

    public function update(string $table, array $data, array $conditions = []): int {
        if ($this->lockdown) return 0;

        $setClauses = [];
        $params = [];
        foreach ($data as $key => $value) {
            $setClauses[] = "$key = ?";
            $params[] = $value;
        }

        $query = "UPDATE $table SET " . implode(", ", $setClauses);

        if (!empty($conditions)) {
            $whereClauses = [];
            foreach ($conditions as $key => $value) {
                $whereClauses[] = "$key = ?";
                $params[] = $value;
            }
            $query .= " WHERE " . implode(" AND ", $whereClauses);
        }

        $this->executeQuery($query, $params);
        return $this->link->affected_rows;
    }

    public function delete(string $table, array $conditions = []): int {
        if ($this->lockdown) return 0;

        $query = "DELETE FROM $table";
        $params = [];

        if (!empty($conditions)) {
            $whereClauses = [];
            foreach ($conditions as $key => $value) {
                $whereClauses[] = "$key = ?";
                $params[] = $value;
            }
            $query .= " WHERE " . implode(" AND ", $whereClauses);
        }

        $this->executeQuery($query, $params);
        return $this->link->affected_rows;
    }
}


/*
*** EXAMPLES OF/FOR USE BELOW ***


include 'SQLQuery.php';
$sql = new SQLQuery();
$sql->connect("localhost", "root", "", "my_database");

// SELECT

$GetItems = $sql->select($CollectionItems, ['CollectionID' => $g['CollectionID']], 'ItemOrder ASC');
$GetBrands = $sql->select($CollectionEntities, ['EntityType' => 'brand', 'EntityCategory' => 'Skate-Brands'], 'EntityName ASC');
$sql->select($ContentHome, ["PageID" => $PageID]);
$result = $sql->select("users", ["username" => "admin"]);
print_r($result);

// SELECT2
$TestThis = $sql->select2("SELECT * FROM $pillarTest WHERE SetID = '" . $g['SetID'] . "' ORDER BY CatID DESC");

// INSERT

$data = [
    'name' => 'Wildflower Honey',
    'price' => 14.99,
    'stock' => 50
];

$insertId = $sql->insert('products', $data);

if ($insertId) {
    echo "Product successfully added with ID: " . $insertId;
} else {
    echo "Failed to add product.";
}

// UPDATE

$data = [
    'price' => 16.99,
    'stock' => 45
];
$conditions = [
    'name' => 'Wildflower Honey'
];

$affectedRows = $sql->update('products', $data, $conditions);

if ($affectedRows > 0) {
    echo "Product successfully updated!";
} else {
    echo "Update failed or no changes made.";
}

$data2 = [
    'LastEditTime' => $AddTime,
    'LastAssetAddTime' => $AddTime,
    'LastAssetID' => $addId,
    'AssetCount' => ($cData[0]['AssetCount'] ?? 0) + 1
];
$sql->update($Collections, $data2, ['id' => $CollectionID]);


// DELETE

$conditions = [
    'name' => 'Wildflower Honey'
];

$deletedRows = $sql->delete('products', $conditions);

if ($deletedRows > 0) {
    echo "Product successfully deleted!";
} else {
    echo "Deletion failed or product not found.";
}

// End of SQLQuery.php
// MIT License — Use freely, no warranty, keep attribution if you're kind.
*/
?>
