<?php
/**
 * LMS External API Handler
 * Fetches data from ArtisansLMS API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/api_auth.php'; // ← add
requireApiKey();  

define('LMS_API_URL', getenv('LMS_API_URL'));
define('LMS_API_KEY', getenv('LMS_API_KEY'));


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$action = isset($input['action']) ? $input['action'] : '';
$source = isset($input['source']) ? $input['source'] : '';
$year = isset($input['year']) ? $input['year'] : '';
$term = isset($input['term']) ? $input['term'] : '';

if ($action !== 'fetch_external') {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}

if ($source === 'lms') {
    fetchLMSData($year, $term);
} else if ($source === 'faculty_eval') {
    fetchFacultyEvalData($year, $term);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data source']);
}

function fetchLMSData($year, $term) {
    $url = LMS_API_URL . '?action=get_overview';
    
    if (!empty($year)) {
        $url .= '&year=' . urlencode($year);
    }
    
    if (!empty($term) && $term !== 'Annual') {
        $semester = str_replace(['st', 'nd', 'rd', 'th'], '', $term);
        $semester = trim($semester);
        $url .= '&semester=' . urlencode($semester);
    }
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'X-API-Key: ' . LMS_API_KEY,
            'Accept: application/json'
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        echo json_encode(['success' => false, 'message' => 'cURL Error: ' . $curlError]);
        return;
    }
    
    if ($httpCode !== 200) {
        echo json_encode(['success' => false, 'message' => 'API returned HTTP code: ' . $httpCode]);
        return;
    }
    
    $data = json_decode($response, true);
    
    if ($data === null) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON response from LMS API']);
        return;
    }
    
    // Extract the data object from {status: 'success', data: {...}}
    if (isset($data['status']) && $data['status'] === 'success' && isset($data['data'])) {
        $result = $data['data'];
    } else if (isset($data[0]) && is_array($data[0])) {
        $result = $data[0];
    } else {
        $result = $data;
    }
    
    echo json_encode(['success' => true, 'data' => $result]);
}

function fetchFacultyEvalData($year, $term) {
    // Mock data - replace with actual faculty evaluation API
    $mockData = [
        'avg_rating'        => 4.2,   // Average faculty rating given by students (scale 1-5)
        'response_rate'     => 85.5,  // % of students who completed the evaluation form
        'total_responses'   => 342,   // Total evaluation forms submitted
        'total_classes'     => 7,     // Total classes that were evaluated
        'total_faculty'     => 5,     // Total number of faculty members evaluated
        'above_threshold'   => 4,     // Number of faculty rated 4.0 and above
        'below_threshold'   => 1,     // Number of faculty rated below 4.0
        'highest_rating'    => 4.8,   // Highest individual faculty rating
        'lowest_rating'     => 3.6,   // Lowest individual faculty rating
        'completion_rate'   => 78.5,  // % of faculty evaluations fully completed (all questions answered)
    ];
    
    echo json_encode(['success' => true, 'data' => $mockData]);
}
?>