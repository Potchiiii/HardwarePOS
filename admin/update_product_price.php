<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error'=>'Unauthorized']);
    exit();
}
require_once '../db.php';
try{
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['id'])) { http_response_code(400); echo json_encode(['error'=>'ID required']); exit(); }
    
    $id = $data['id'];
    $price = $data['price'] === '' ? null : $data['price'];
    $pricingType = $data['pricingType'] ?? 'unit';
    
    // Map pricing type to database column
    $retailColumnMap = [
        'unit' => 'per_unit',
        'length' => 'per_length',
        'kilo' => 'per_kilo'
    ];
    
    // Handle wholesale separately
    if ($pricingType === 'wholesale') {
        $deductionUnits = $data['deductionUnits'] === '' || $data['deductionUnits'] === null ? null : $data['deductionUnits'];
        $deductionMeters = $data['deductionMeters'] === '' || $data['deductionMeters'] === null ? null : $data['deductionMeters'];
        $deductionKilos = $data['deductionKilos'] === '' || $data['deductionKilos'] === null ? null : $data['deductionKilos'];
        
        $stmt = $pdo->prepare("UPDATE inventory SET whole_sale = ?, wholesale_deduction_units = ?, wholesale_deduction_meters = ?, wholesale_deduction_kilos = ? WHERE id = ?");
        $stmt->execute([$price, $deductionUnits, $deductionMeters, $deductionKilos, $id]);
    } else {
        // Handle retail pricing
        if (!isset($retailColumnMap[$pricingType])) {
            http_response_code(400);
            echo json_encode(['error'=>'Invalid pricing type']);
            exit();
        }
        
        $column = $retailColumnMap[$pricingType];
        
        // Clear all other retail pricing columns and set only the selected one
        $updateQuery = "UPDATE inventory SET ";
        $setParts = [];
        $params = [];
        
        foreach ($retailColumnMap as $type => $col) {
            if ($type === $pricingType) {
                $setParts[] = "$col = ?";
                $params[] = $price;
            } else {
                $setParts[] = "$col = NULL";
            }
        }
        $updateQuery .= implode(", ", $setParts) . " WHERE id = ?";
        $params[] = $id;
        
        $stmt = $pdo->prepare($updateQuery);
        $stmt->execute($params);
    }
    echo json_encode(['success'=>true]);
}catch(Exception $e){ http_response_code(500); echo json_encode(['error'=>$e->getMessage()]); }
?>
