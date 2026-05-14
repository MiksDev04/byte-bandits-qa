<?php
/**
 * Policies API - CRUD Operations
 * backend/api/policies_api.php
 *
 * PDF upload is handled client-side (Cloudinary unsigned preset).
 * This API receives the resulting secure_url and stores it as document_url.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();
require_once '../config/database.php';
require_once '../config/api_auth.php';
requireApiKey();

// Check authentication
if (empty($_SESSION['logged_in'])) {
    jsonResponse(false, 'Unauthorized access', [], 401);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

try {
    switch ($method) {
        case 'GET':
            if ($action === 'get' && isset($_GET['id'])) {
                getPolicyById((int) $_GET['id']);
            } elseif ($action === 'list') {
                listPolicies();
            } else {
                jsonResponse(false, 'Invalid action');
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                jsonResponse(false, 'Invalid JSON data');
            }

            $act = $data['action'] ?? null;
            if ($act === 'delete') {
                deletePolicy((int) ($data['id'] ?? 0));
            } elseif ($act === 'create') {
                createPolicy($data);
            } elseif ($act === 'update') {
                updatePolicy($data);
            } elseif (!empty($data['policy_id'])) {
                updatePolicy($data);
            } else {
                createPolicy($data);
            }
            break;

        default:
            jsonResponse(false, 'Method not allowed', [], 405);
    }
} catch (Exception $e) {
    error_log('Policies API Error: ' . $e->getMessage());
    jsonResponse(false, 'Server error occurred', [], 500);
}

/* ─────────────────────────────────────────────────────────────────────────── */

function listPolicies(): void
{
    $conn   = getDBConnection();
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status = (isset($_GET['status']) && $_GET['status'] !== 'all') ? $_GET['status'] : '';

    $sql    = "
        SELECT p.*, s.title AS standard_title, s.body AS standard_body
        FROM   qa_policies  p
        LEFT JOIN qa_standards s ON p.standard_id = s.standard_id
        WHERE  1 = 1
    ";
    $params = [];
    $types  = '';

    if ($search !== '') {
        $sql    .= " AND (p.title LIKE ? OR p.content LIKE ? OR s.title LIKE ?)";
        $like    = "%{$search}%";
        $params  = array_merge($params, [$like, $like, $like]);
        $types  .= 'sss';
    }

    if ($status !== '') {
        $sql    .= " AND p.status = ?";
        $params[] = $status;
        $types  .= 's';
    }

    $sql .= " ORDER BY p.created_date DESC, p.policy_id DESC";

    $stmt = $conn->prepare($sql);
    if ($types && count($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $policies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    jsonResponse(true, 'Policies retrieved', ['data' => $policies]);
}

function getPolicyById(int $id): void
{
    if ($id <= 0) { jsonResponse(false, 'Invalid policy ID'); }

    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT p.*, s.title AS standard_title
        FROM   qa_policies   p
        LEFT JOIN qa_standards s ON p.standard_id = s.standard_id
        WHERE  p.policy_id = ?
    ");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $policy = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $policy
        ? jsonResponse(true,  'Policy found',     ['data' => $policy])
        : jsonResponse(false, 'Policy not found');
}

function createPolicy(array $data): void
{
    $errors = validatePolicyData($data);
    if (!empty($errors)) {
        jsonResponse(false, 'Validation failed', ['errors' => $errors]);
    }

    $conn         = getDBConnection();
    $title        = trim($data['title']);
    $standard_id  = !empty($data['standard_id']) ? (int) $data['standard_id'] : null;
    $content      = trim($data['content']);
    $document_url = !empty($data['document_url']) ? trim($data['document_url']) : null;
    $created_date = !empty($data['created_date']) ? $data['created_date'] : date('Y-m-d');
    $status       = $data['status'] ?? 'Active';

    if ($standard_id && !standardExists($conn, $standard_id)) {
        jsonResponse(false, 'Validation failed', ['errors' => ['standard_id' => 'Selected standard does not exist']]);
        return;
    }

    $stmt = $conn->prepare("
        INSERT INTO qa_policies (standard_id, title, content, document_url, created_date, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('isssss', $standard_id, $title, $content, $document_url, $created_date, $status);

    if ($stmt->execute()) {
        $id = $conn->insert_id;
        $stmt->close();
        jsonResponse(true, 'Policy created successfully', ['policy_id' => $id]);
    } else {
        $stmt->close();
        jsonResponse(false, 'Failed to create policy');
    }
}

function updatePolicy(array $data): void
{
    $id = (int) ($data['policy_id'] ?? 0);
    if ($id <= 0) { jsonResponse(false, 'Invalid policy ID'); }

    $errors = validatePolicyData($data, true);
    if (!empty($errors)) {
        jsonResponse(false, 'Validation failed', ['errors' => $errors]);
    }

    $conn         = getDBConnection();
    $title        = trim($data['title']);
    $standard_id  = !empty($data['standard_id']) ? (int) $data['standard_id'] : null;
    $content      = trim($data['content']);
    $document_url = !empty($data['document_url']) ? trim($data['document_url']) : null;
    $created_date = !empty($data['created_date']) ? $data['created_date'] : date('Y-m-d');
    $status       = $data['status'] ?? 'Active';

    if ($standard_id && !standardExists($conn, $standard_id)) {
        jsonResponse(false, 'Validation failed', ['errors' => ['standard_id' => 'Selected standard does not exist']]);
        return;
    }

    $stmt = $conn->prepare("
        UPDATE qa_policies
        SET    standard_id = ?, title = ?, content = ?, document_url = ?,
               created_date = ?, status = ?
        WHERE  policy_id = ?
    ");
    $stmt->bind_param('isssssi', $standard_id, $title, $content, $document_url, $created_date, $status, $id);

    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(true, 'Policy updated successfully');
    } else {
        $stmt->close();
        jsonResponse(false, 'Failed to update policy');
    }
}

function deletePolicy(int $id): void
{
    if ($id <= 0) { jsonResponse(false, 'Invalid policy ID'); }

    $conn = getDBConnection();
    $stmt = $conn->prepare("DELETE FROM qa_policies WHERE policy_id = ?");
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(true, 'Policy deleted successfully');
    } else {
        $stmt->close();
        jsonResponse(false, 'Failed to delete policy');
    }
}

/* ── Helpers ──────────────────────────────────────────────────────────────── */

function standardExists(mysqli $conn, int $id): bool
{
    $stmt = $conn->prepare("SELECT standard_id FROM qa_standards WHERE standard_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $exists;
}

function validatePolicyData(array $data, bool $isUpdate = false): array
{
    $errors = [];

    if (empty(trim($data['title'] ?? ''))) {
        $errors['title'] = 'Title is required';
    } elseif (strlen($data['title']) > 150) {
        $errors['title'] = 'Title must not exceed 150 characters';
    }

    if (empty(trim($data['content'] ?? ''))) {
        $errors['content'] = 'Content is required';
    }

    // document_url is now a Cloudinary URL set by the frontend; validate if present
    if (!empty($data['document_url'])) {
        if (!filter_var($data['document_url'], FILTER_VALIDATE_URL)) {
            $errors['document_url'] = 'Invalid document URL';
        } elseif (strlen($data['document_url']) > 500) {
            $errors['document_url'] = 'Document URL is too long';
        }
    }

    if (!empty($data['created_date']) && !validateDate($data['created_date'])) {
        $errors['created_date'] = 'Please enter a valid date';
    }

    if (!empty($data['status']) && !in_array($data['status'], ['Active', 'Archived'], true)) {
        $errors['status'] = 'Invalid status value';
    }

    return $errors;
}

function validateDate(string $date, string $format = 'Y-m-d'): bool
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}