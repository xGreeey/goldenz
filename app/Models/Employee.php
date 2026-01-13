<?php
/**
 * Employee Model
 * Handles employee data operations
 */

namespace App\Models;

use App\Core\Database;

class Employee
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all employees
     * 
     * @param array $filters
     * @return array
     */
    public function getAll($filters = [])
    {
        $sql = "SELECT * FROM employees WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['type'])) {
            $sql .= " AND employee_type = ?";
            $params[] = $filters['type'];
        }

        $sql .= " ORDER BY created_at DESC";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get employee by ID
     * 
     * @param int $id
     * @return array|null
     */
    public function findById($id)
    {
        $sql = "SELECT * FROM employees WHERE id = ? LIMIT 1";
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Create new employee
     * 
     * @param array $data
     * @return int|false Employee ID or false on failure
     */
    public function create($data)
    {
        $sql = "INSERT INTO employees (
            employee_no, employee_type, surname, first_name, middle_name, 
            post, license_no, license_exp_date, rlm_exp, date_hired, 
            cp_number, sss_no, pagibig_no, tin_number, philhealth_no, 
            birth_date, height, weight, address, contact_person, 
            relationship, contact_person_address, contact_person_number, 
            blood_type, religion, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $params = [
            $data['employee_no'],
            $data['employee_type'] ?? 'SG',
            $data['surname'],
            $data['first_name'],
            $data['middle_name'] ?? null,
            $data['post'],
            $data['license_no'] ?? null,
            $data['license_exp_date'] ?? null,
            $data['rlm_exp'] ?? null,
            $data['date_hired'],
            $data['cp_number'] ?? null,
            $data['sss_no'] ?? null,
            $data['pagibig_no'] ?? null,
            $data['tin_number'] ?? null,
            $data['philhealth_no'] ?? null,
            $data['birth_date'] ?? null,
            $data['height'] ?? null,
            $data['weight'] ?? null,
            $data['address'] ?? null,
            $data['contact_person'] ?? null,
            $data['relationship'] ?? null,
            $data['contact_person_address'] ?? null,
            $data['contact_person_number'] ?? null,
            $data['blood_type'] ?? null,
            $data['religion'] ?? null,
            $data['status'] ?? 'Active',
        ];

        try {
            $this->db->query($sql, $params);
            return $this->db->getConnection()->lastInsertId();
        } catch (\Exception $e) {
            error_log('Employee creation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update employee
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data)
    {
        $sql = "UPDATE employees SET 
            surname = ?, first_name = ?, middle_name = ?, post = ?, 
            cp_number = ?, employee_no = ?, sss_no = ?, pagibig_no = ?, 
            tin_number = ?, philhealth_no = ?, status = ?, updated_at = NOW()
            WHERE id = ?";

        $params = [
            $data['surname'],
            $data['first_name'],
            $data['middle_name'],
            $data['post'],
            $data['cp_number'],
            $data['employee_no'],
            $data['sss'],
            $data['pag_ibig'],
            $data['tin'],
            $data['philhealth'],
            $data['status'],
            $id
        ];

        try {
            $this->db->query($sql, $params);
            return true;
        } catch (\Exception $e) {
            error_log('Employee update error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete employee
     * 
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $sql = "DELETE FROM employees WHERE id = ?";
        try {
            $this->db->query($sql, [$id]);
            return true;
        } catch (\Exception $e) {
            error_log('Employee deletion error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get dashboard statistics
     * 
     * @return array
     */
    public function getDashboardStats()
    {
        $stats = [];

        // Total employees
        $result = $this->db->fetch("SELECT COUNT(*) as total FROM employees");
        $stats['total_employees'] = $result['total'] ?? 0;

        // Active employees
        $result = $this->db->fetch("SELECT COUNT(*) as active FROM employees WHERE status = 'Active'");
        $stats['active_employees'] = $result['active'] ?? 0;

        // Security personnel
        $result = $this->db->fetch("SELECT COUNT(*) as security FROM employees WHERE employee_type IN ('SG', 'LG') AND status = 'Active'");
        $stats['security_personnel'] = $result['security'] ?? 0;

        return $stats;
    }
}

